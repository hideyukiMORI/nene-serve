<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Marketplace\PdoBillingPeriodRepository;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Real SELECT/hydration of PdoBillingPeriodRepository: status, the period window,
 * and tenant scoping. (save() is a MySQL upsert, so rows are seeded with plain
 * INSERT.)
 */
final class PdoBillingPeriodRepositoryTest extends TestCase
{
    private DatabaseQueryExecutorInterface $db;
    private PdoBillingPeriodRepository $repo;

    protected function setUp(): void
    {
        $this->db = TestDatabase::withSchema('billing_periods');
        $this->repo = new PdoBillingPeriodRepository($this->db);
    }

    /** @param array<string, scalar|null> $overrides */
    private function seed(array $overrides = []): void
    {
        TestDatabase::seed($this->db, 'billing_periods', $overrides + [
            'id' => 'bp-1',
            'organization_id' => 'org-1',
            'campaign_id' => 'cmp-1',
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'status' => 'open',
        ]);
    }

    public function testHydratesEveryField(): void
    {
        $this->seed(['status' => 'closed']);

        $period = $this->repo->findByIdInOrganization('bp-1', 'org-1');

        self::assertNotNull($period);
        self::assertSame('cmp-1', $period->campaignId);
        self::assertSame('2026-06-01', $period->periodStart);
        self::assertSame('2026-06-30', $period->periodEnd);
        self::assertSame('closed', $period->status);
    }

    public function testTenantScoped(): void
    {
        $this->seed();

        self::assertNotNull($this->repo->findByIdInOrganization('bp-1', 'org-1'));
        self::assertNull($this->repo->findByIdInOrganization('bp-1', 'org-OTHER'));
    }

    public function testReturnsNullWhenAbsent(): void
    {
        self::assertNull($this->repo->findByIdInOrganization('bp-missing', 'org-1'));
    }
}
