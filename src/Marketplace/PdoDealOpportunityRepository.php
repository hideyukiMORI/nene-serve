<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use PDO;

final class PdoDealOpportunityRepository implements DealOpportunityRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, campaign_id, external_reference, amount_cents, status, opportunity_id, created_at';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByExternalReference(string $organizationId, string $externalReference): ?DealOpportunity
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM deal_opportunities WHERE organization_id = ? AND external_reference = ? LIMIT 1');
        $stmt->execute([$organizationId, $externalReference]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function save(DealOpportunity $opportunity): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO deal_opportunities (id, organization_id, campaign_id, external_reference, amount_cents, status, opportunity_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE campaign_id = new.campaign_id, external_reference = new.external_reference, amount_cents = new.amount_cents, status = new.status, opportunity_id = new.opportunity_id',
        );
        $stmt->execute([
            $opportunity->id,
            $opportunity->organizationId,
            $opportunity->campaignId,
            $opportunity->externalReference,
            $opportunity->amountCents,
            $opportunity->status,
            $opportunity->opportunityId,
            $opportunity->createdAt,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): DealOpportunity
    {
        return new DealOpportunity(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['campaign_id'],
            (string) $row['external_reference'],
            (int) $row['amount_cents'],
            (string) $row['status'],
            $row['opportunity_id'] !== null ? (string) $row['opportunity_id'] : null,
            (string) $row['created_at'],
        );
    }
}
