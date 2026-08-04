<?php

declare(strict_types=1);

namespace NeneServe\Tests\Integration;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Serving\Token\PdoTokenStore;
use NeneServe\Tests\Support\FixedClock;
use NeneServe\Tests\Support\PgsqlTestDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Single-use redemption on real PostgreSQL (PG support, issue #120).
 *
 * `PdoTokenStore` writes one statement for every dialect — there is no branch to
 * diverge. What differs is the driver: the store decides "first redemption" from
 * the affected-row count, and that value comes from PDO, not from SQL. This
 * pins it on the second target rather than assuming MySQL's answer carries over.
 */
#[Group('integration')]
final class PublicTokenSingleUsePgsqlTest extends TestCase
{
    private DatabaseQueryExecutorInterface $db;

    private string $organizationId;

    protected function setUp(): void
    {
        $db = PgsqlTestDatabase::fromEnv();

        if ($db === null) {
            self::markTestSkipped('PostgreSQL integration DB not configured (set PGSQL_TEST_HOST).');
        }

        $this->db = $db;
        $this->organizationId = 'it-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            $this->db->execute('DELETE FROM public_tokens WHERE organization_id = ?', [$this->organizationId]);
        }
    }

    public function testAClickTokenIsRedeemedExactlyOnceOnPostgres(): void
    {
        $token = $this->store()->issueClickToken(
            $this->organizationId,
            'plc-1',
            'crv-1',
            'https://example.com/landing',
            900,
        );

        self::assertNotNull($this->store()->consumeClickToken($token));
        self::assertNull($this->store()->consumeClickToken($token), 'Single-use (ADR 0019 §2).');
    }

    public function testAnImpressionReplayReportsAlreadyRecordedOnPostgres(): void
    {
        $token = $this->store()->issueImpressionToken($this->organizationId, 'plc-1', 'crv-1');

        $first = $this->store()->recordImpression($token);
        $replay = $this->store()->recordImpression($token);

        self::assertNotNull($first);
        self::assertFalse($first->alreadyRecorded);
        self::assertNotNull($replay);
        self::assertTrue($replay->alreadyRecorded);
    }

    public function testAnExpiredClickTokenFailsClosedOnPostgres(): void
    {
        $token = $this->store('2026-08-04T12:00:00+00:00')->issueClickToken(
            $this->organizationId,
            'plc-1',
            'crv-1',
            'https://example.com/landing',
            900,
        );

        self::assertNull($this->store('2026-08-04T12:15:01+00:00')->consumeClickToken($token));
    }

    private function store(string $instant = '2026-08-04T12:00:00+00:00'): PdoTokenStore
    {
        return new PdoTokenStore($this->db, new FixedClock($instant));
    }
}
