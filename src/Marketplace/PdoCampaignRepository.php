<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use PDO;

final class PdoCampaignRepository implements CampaignRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, advertiser_id, name, pricing_rule_id, budget_cents, status, funding_status, pause_on_budget_exhausted, archived_at';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Campaign
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM campaigns WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function listByOrganization(string $organizationId): array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM campaigns WHERE organization_id = ? ORDER BY name');
        $stmt->execute([$organizationId]);

        return array_map($this->hydrate(...), array_values($stmt->fetchAll()));
    }

    public function save(Campaign $campaign): void
    {
        $stmt = $this->pdo->prepare(
            'REPLACE INTO campaigns
                (id, organization_id, advertiser_id, name, pricing_rule_id, budget_cents, status, funding_status, pause_on_budget_exhausted, archived_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
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
        ]);
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
