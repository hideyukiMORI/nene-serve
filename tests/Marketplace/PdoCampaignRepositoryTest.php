<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Marketplace\FundingStatus;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Real SELECT/hydration of PdoCampaignRepository: funding-status enum, integer
 * budget, the boolean auto-pause flag, nullable archive tombstone, and tenant
 * scoping. (save() is a MySQL upsert, so rows are seeded with plain INSERT.)
 */
final class PdoCampaignRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $db;
    private PdoCampaignRepository $repo;

    protected function setUp(): void
    {
        $this->db = TestDatabase::withSchema('campaigns');
        $this->repo = new PdoCampaignRepository($this->db);
    }

    /** @param array<string, scalar|null> $overrides */
    private function seed(array $overrides = []): void
    {
        TestDatabase::seed($this->db, 'campaigns', $overrides + [
            'id' => 'cmp-1',
            'organization_id' => 'org-1',
            'advertiser_id' => 'adv-1',
            'name' => 'Spring',
            'pricing_rule_id' => 'pr-1',
            'budget_cents' => 100_000,
            'status' => 'active',
            'funding_status' => 'funded',
            'pause_on_budget_exhausted' => 1,
            'archived_at' => null,
        ]);
    }

    public function testHydratesFundedActiveCampaign(): void
    {
        $this->seed();

        $campaign = $this->repo->findByIdInOrganization('cmp-1', 'org-1');

        self::assertNotNull($campaign);
        self::assertSame(100_000, $campaign->budgetCents);
        self::assertSame(FundingStatus::Funded, $campaign->fundingStatus);
        self::assertTrue($campaign->pauseOnBudgetExhausted);
        self::assertTrue($campaign->isFundedForServe());
    }

    public function testHydratesUnfundedAndArchived(): void
    {
        $this->seed([
            'funding_status' => 'unfunded',
            'pause_on_budget_exhausted' => 0,
            'archived_at' => '2026-06-06T00:00:00+00:00',
            'status' => 'archived',
        ]);

        $campaign = $this->repo->findByIdInOrganization('cmp-1', 'org-1');

        self::assertNotNull($campaign);
        self::assertSame(FundingStatus::Unfunded, $campaign->fundingStatus);
        self::assertFalse($campaign->pauseOnBudgetExhausted);
        self::assertSame('2026-06-06T00:00:00+00:00', $campaign->archivedAt);
        self::assertFalse($campaign->isActive());
    }

    public function testTenantScoped(): void
    {
        $this->seed();

        self::assertNotNull($this->repo->findByIdInOrganization('cmp-1', 'org-1'));
        self::assertNull($this->repo->findByIdInOrganization('cmp-1', 'org-OTHER'));
    }

    public function testListIsTenantScopedAndPaginated(): void
    {
        $this->seed(['id' => 'cmp-1']);
        $this->seed(['id' => 'cmp-2']);
        $this->seed(['id' => 'cmp-3', 'organization_id' => 'org-2']);

        self::assertCount(2, $this->repo->listByOrganization('org-1', 10, 0));
        self::assertCount(1, $this->repo->listByOrganization('org-1', 1, 0));
    }
}
