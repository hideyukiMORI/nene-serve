<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement\Metrics;

use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Measurement\Metrics\ExportMetricsHandler;
use NeneServe\Measurement\Metrics\GetMetricsHandler;
use NeneServe\Measurement\Metrics\GetMetricsUseCase;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Auth\AuthContextRequiredException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class MetricsHandlerTest extends TestCase
{
    private const ADMIN = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];
    private const ANALYST = ['org' => 'org-acme', 'role' => 'analyst', 'sub' => 'u-2'];

    public function testReturnsAggregateReport(): void
    {
        $response = $this->get(self::ADMIN, []);

        self::assertSame(200, $response->getStatusCode());

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertArrayHasKey('rows', $body);
        self::assertArrayHasKey('fill', $body);
        self::assertArrayHasKey('conversions', $body);
    }

    public function testSensitiveReportRequiresCapability(): void
    {
        $response = $this->get(self::ANALYST, ['include_sensitive' => 'true']);

        self::assertSame(403, $response->getStatusCode());
    }

    public function testRejectsRequestWithoutClaims(): void
    {
        $this->expectException(AuthContextRequiredException::class);
        $this->get(null, []);
    }

    public function testExportReturnsCsv(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new ExportMetricsHandler(
            new ExportMetricsUseCase($this->events(), $this->orgId()),
            $psr17,
            $psr17,
        );

        $request = $psr17->createServerRequest('GET', '/admin/metrics/export')
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::ADMIN);

        $response = $handler->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/csv', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @param array<string, mixed>|null $claims
     * @param array<string, string> $query
     */
    private function get(?array $claims, array $query): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new GetMetricsHandler(
            new GetMetricsUseCase($this->events(), $this->transactions(), $this->orgId()),
            new JsonResponseFactory($psr17, $psr17),
            new ProblemDetailsResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('GET', '/admin/metrics')->withQueryParams($query);

        if ($claims !== null) {
            $request = $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
        }

        return $handler->handle($request);
    }

    /** @return RequestScopedHolder<string> */
    private function orgId(): RequestScopedHolder
    {
        /** @var RequestScopedHolder<string> $holder */
        $holder = new RequestScopedHolder();
        $holder->set('org-acme');

        return $holder;
    }

    private function transactions(): DatabaseTransactionManagerInterface
    {
        return new class () implements DatabaseTransactionManagerInterface {
            public function transactional(callable $callback): mixed
            {
                $executor = new class () implements \Nene2\Database\DatabaseQueryExecutorInterface {
                    public function execute(string $sql, array $parameters = []): int
                    {
                        return 0;
                    }

                    public function insert(string $sql, array $parameters = []): int
                    {
                        return 0;
                    }

                    public function lastInsertId(): int
                    {
                        return 0;
                    }

                    public function fetchOne(string $sql, array $parameters = []): ?array
                    {
                        return null;
                    }

                    public function fetchAll(string $sql, array $parameters = []): array
                    {
                        return [];
                    }
                };

                return $callback($executor);
            }
        };
    }

    private function events(): EventStoreInterface
    {
        return new class () implements EventStoreInterface {
            public function recordImpression(\NeneServe\Measurement\ImpressionEvent $event): void
            {
            }

            public function recordClick(\NeneServe\Measurement\ClickEvent $event): void
            {
            }

            public function recordConversion(\NeneServe\Measurement\ConversionEvent $event): void
            {
            }

            public function dailyConversions(string $organizationId, string $fromDate, string $toDate): array
            {
                return [];
            }

            public function recordServeRequest(string $organizationId, string $placementId, bool $filled): void
            {
            }

            public function dailyFillRates(string $organizationId, string $fromDate, string $toDate): array
            {
                return [];
            }

            public function dailyMetrics(string $organizationId, string $fromDate, string $toDate): array
            {
                return [];
            }

            public function billableCountsForCreatives(string $organizationId, array $creativeIds): array
            {
                return ['impressions' => 0, 'clicks' => 0];
            }

            public function visitorBreakdown(string $organizationId, string $fromDate, string $toDate): array
            {
                return [];
            }

            public function exportVisitorData(string $organizationId, string $visitorBucket): array
            {
                return [];
            }

            public function purgeExpiredEvents(string $organizationId, string $ordinaryBefore, string $billingBefore, array $billingCreativeIds): int
            {
                return 0;
            }

            public function eraseVisitor(string $organizationId, string $visitorBucket): int
            {
                return 0;
            }
        };
    }
}
