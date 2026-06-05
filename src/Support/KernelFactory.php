<?php

declare(strict_types=1);

namespace NeneServe\Support;

use Nene2\Database\DatabaseConnectionFactoryInterface;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Assets\PdoAssetRepository;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Http\Kernel;
use NeneServe\Marketplace\Invoice\HttpInvoiceClient;
use NeneServe\Marketplace\Invoice\InvoiceClientInterface;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\PdoBillingPeriodRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PdoDealOpportunityRepository;
use NeneServe\Marketplace\PdoInvoiceHandoffRepository;
use NeneServe\Marketplace\PdoPricingRuleRepository;
use NeneServe\Marketplace\PdoSpendSnapshotRepository;
use NeneServe\Mcp\PdoChangePlanRepository;
use NeneServe\Measurement\FileEventStore;
use NeneServe\Measurement\PdoEventStore;
use NeneServe\Retention\PdoLegalHoldRepository;
use NeneServe\Service\PdoServiceTokenRepository;
use NeneServe\Serving\Frequency\FileFrequencyCapStore;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Scan\BundleScannerInterface;
use NeneServe\Serving\Scan\ClamAvScanner;
use NeneServe\Serving\Token\FileTokenStore;
use NeneServe\Settings\PdoSmtpSettingsRepository;
use NeneServe\Storage\LocalStorage;
use NeneServe\Tenant\PdoInvitationRepository;
use NeneServe\Tenant\PdoOrganizationRepository;
use NeneServe\Tenant\PdoUserRepository;
use NeneServe\Upstream\Deal\DealClientInterface;
use NeneServe\Upstream\Deal\HttpDealClient;
use NeneServe\Upstream\Records\HttpRecordsClient;
use NeneServe\Upstream\Records\RecordsClientInterface;
use PDO;

/**
 * Builds the live {@see Kernel} with the right persistence for the environment.
 *
 * - **database** mode (when `DB_HOST` is set): every governed entity is read and
 *   written through its `Pdo*` repository against MySQL, mutations are wrapped by
 *   {@see PdoTransactionManager}, and the audit trail / event store are persisted.
 * - **development** mode (default, for `php -S`): file-backed stores keep tokens,
 *   events and frequency state across requests; entity repositories use the
 *   in-memory {@see DevFixtures} seed (see Kernel defaults).
 *
 * Token store, frequency store and the rate limiter have no PDO implementation;
 * they stay file-/process-backed and are suited to a single application host.
 * Secrets (DB credentials, sibling tokens) come from the environment only
 * (api-security §6); none are committed.
 */
final class KernelFactory
{
    /**
     * @param array<string, string>|null $env explicit environment for testing;
     *                                         falls back to the process environment.
     */
    public static function create(string $storageDir, ?array $env = null): Kernel
    {
        $read = static function (string $key) use ($env): ?string {
            if ($env !== null) {
                return $env[$key] ?? null;
            }
            $value = getenv($key);

            return $value === false ? null : $value;
        };

        $dbHost = $read('DB_HOST');
        if ($dbHost !== null && $dbHost !== '') {
            return self::database(Database::fromEnv(), $storageDir, $read);
        }

        return self::development($storageDir, $read);
    }

    /**
     * Wire every PDO repository against a single connection. Exposed directly so
     * an integration test can inject a live connection.
     *
     * @param callable(string): ?string $read
     */
    public static function database(PDO $pdo, string $storageDir, callable $read): Kernel
    {
        // Repositories ported to the NENE2 query executor (Phase 2) are wired with
        // an executor bound to this same connection so the transaction manager
        // still wraps their writes; not-yet-ported repos keep the raw PDO.
        $query = new PdoDatabaseQueryExecutor(
            new class ($pdo) implements DatabaseConnectionFactoryInterface {
                public function __construct(private readonly PDO $pdo)
                {
                }

                public function create(): PDO
                {
                    return $this->pdo;
                }
            },
            $pdo,
        );

        return new Kernel(
            users: new PdoUserRepository($query),
            organizations: new PdoOrganizationRepository($query),
            placements: new PdoPlacementRepository($pdo),
            creatives: new PdoCreativeRepository($pdo),
            tokens: new FileTokenStore($storageDir . '/tokens.json'),
            serviceTokens: new PdoServiceTokenRepository($pdo),
            audit: new PdoAuditLog($pdo),
            events: new PdoEventStore($pdo),
            frequencyCaps: new FileFrequencyCapStore($storageDir . '/frequency.json'),
            tx: new PdoTransactionManager($pdo),
            advertisers: new PdoAdvertiserRepository($pdo),
            pricingRules: new PdoPricingRuleRepository($pdo),
            campaigns: new PdoCampaignRepository($pdo),
            billingPeriods: new PdoBillingPeriodRepository($pdo),
            spendSnapshots: new PdoSpendSnapshotRepository($pdo),
            invoiceHandoffs: new PdoInvoiceHandoffRepository($pdo),
            invoiceClient: self::invoiceClient($read),
            legalHolds: new PdoLegalHoldRepository($pdo),
            records: self::recordsClient($read),
            dealOpportunities: new PdoDealOpportunityRepository($pdo),
            dealClient: self::dealClient($read),
            changePlans: new PdoChangePlanRepository($pdo),
            smtpSettings: new PdoSmtpSettingsRepository($pdo),
            invitations: new PdoInvitationRepository($pdo),
            assets: new PdoAssetRepository($pdo),
            storage: new LocalStorage($storageDir . '/uploads'),
            scanner: self::scanner($read),
        );
    }

    /** @param callable(string): ?string $read */
    private static function development(string $storageDir, callable $read): Kernel
    {
        return new Kernel(
            tokens: new FileTokenStore($storageDir . '/tokens.json'),
            events: new FileEventStore($storageDir . '/events.json'),
            frequencyCaps: new FileFrequencyCapStore($storageDir . '/frequency.json'),
            invoiceClient: self::invoiceClient($read),
            records: self::recordsClient($read),
            dealClient: self::dealClient($read),
            storage: new LocalStorage($storageDir . '/uploads'),
            scanner: self::scanner($read),
        );
    }

    /**
     * Real ClamAV (clamd) when CLAMAV_HOST is set; otherwise the kernel default
     * StubBundleScanner. The scanner is fail-closed when unreachable.
     *
     * @param callable(string): ?string $read
     */
    private static function scanner(callable $read): ?BundleScannerInterface
    {
        $host = $read('CLAMAV_HOST');
        if ($host === null || $host === '') {
            return null;
        }
        $port = $read('CLAMAV_PORT');

        return new ClamAvScanner($host, $port !== null && $port !== '' ? (int) $port : 3310);
    }

    /**
     * Sibling clients are real only when configured; otherwise the Kernel default
     * Fake is used. HTTP only, contract-accurate, failure-isolated.
     *
     * @param callable(string): ?string $read
     */
    private static function invoiceClient(callable $read): ?InvoiceClientInterface
    {
        $base = $read('NENE_INVOICE_API_BASE_URL');
        $token = $read('NENE_INVOICE_SERVICE_TOKEN');

        return self::configured($base, $token) ? new HttpInvoiceClient($base, $token) : null;
    }

    /** @param callable(string): ?string $read */
    private static function recordsClient(callable $read): ?RecordsClientInterface
    {
        $base = $read('NENE_RECORDS_API_BASE_URL');
        $token = $read('NENE_RECORDS_SERVICE_TOKEN');

        return self::configured($base, $token) ? new HttpRecordsClient($base, $token) : null;
    }

    /** @param callable(string): ?string $read */
    private static function dealClient(callable $read): ?DealClientInterface
    {
        $base = $read('NENE_DEAL_API_BASE_URL');
        $token = $read('NENE_DEAL_SERVICE_TOKEN');

        return self::configured($base, $token) ? new HttpDealClient($base, $token) : null;
    }

    /**
     * @phpstan-assert-if-true non-empty-string $base
     * @phpstan-assert-if-true non-empty-string $token
     */
    private static function configured(?string $base, ?string $token): bool
    {
        return $base !== null && $base !== '' && $token !== null && $token !== '';
    }
}
