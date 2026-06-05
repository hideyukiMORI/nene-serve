<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoDealOpportunityRepository implements DealOpportunityRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, campaign_id, external_reference, amount_cents, status, opportunity_id, created_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByExternalReference(string $organizationId, string $externalReference): ?DealOpportunity
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM deal_opportunities WHERE organization_id = ? AND external_reference = ? LIMIT 1',
            [$organizationId, $externalReference],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(DealOpportunity $opportunity): void
    {
        $this->query->execute(
            'INSERT INTO deal_opportunities (id, organization_id, campaign_id, external_reference, amount_cents, status, opportunity_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE campaign_id = new.campaign_id, external_reference = new.external_reference, amount_cents = new.amount_cents, status = new.status, opportunity_id = new.opportunity_id',
            [
                $opportunity->id,
                $opportunity->organizationId,
                $opportunity->campaignId,
                $opportunity->externalReference,
                $opportunity->amountCents,
                $opportunity->status,
                $opportunity->opportunityId,
                $opportunity->createdAt,
            ],
        );
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
