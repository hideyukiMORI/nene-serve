<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoCampaignRepository implements CampaignRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, advertiser_id, name, pricing_rule_id, budget_cents, status, funding_status, pause_on_budget_exhausted, archived_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Campaign
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM campaigns WHERE id = ? AND organization_id = ? LIMIT 1',
            [$id, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM campaigns WHERE organization_id = ? ORDER BY name LIMIT ? OFFSET ?',
            [$organizationId, $limit, $offset],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function save(Campaign $campaign): void
    {
        $this->query->execute(
            'INSERT INTO campaigns (id, organization_id, advertiser_id, name, pricing_rule_id, budget_cents, status, funding_status, pause_on_budget_exhausted, archived_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE advertiser_id = new.advertiser_id, name = new.name, pricing_rule_id = new.pricing_rule_id, budget_cents = new.budget_cents, status = new.status, funding_status = new.funding_status, pause_on_budget_exhausted = new.pause_on_budget_exhausted, archived_at = new.archived_at',
            [
                $campaign->id,
                $campaign->organizationId,
                $campaign->advertiserId,
                $campaign->name,
                $campaign->pricingRuleId,
                $campaign->budgetCents,
                $campaign->status,
                $campaign->fundingStatus->value,
                $campaign->pauseOnBudgetExhausted ? 1 : 0,
                $campaign->archivedAt,
            ],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Campaign
    {
        return new Campaign(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['advertiser_id'],
            (string) $row['name'],
            (string) $row['pricing_rule_id'],
            (int) $row['budget_cents'],
            (string) $row['status'],
            FundingStatus::from((string) $row['funding_status']),
            (bool) $row['pause_on_budget_exhausted'],
            $row['archived_at'] !== null ? (string) $row['archived_at'] : null,
        );
    }
}
