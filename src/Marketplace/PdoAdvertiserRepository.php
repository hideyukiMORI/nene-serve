<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use PDO;

final class PdoAdvertiserRepository implements AdvertiserRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, name, status, invoice_client_id, disabled_at';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Advertiser
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM advertisers WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function listByOrganization(string $organizationId): array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM advertisers WHERE organization_id = ? ORDER BY name');
        $stmt->execute([$organizationId]);

        return array_map($this->hydrate(...), array_values($stmt->fetchAll()));
    }

    public function save(Advertiser $advertiser): void
    {
        $stmt = $this->pdo->prepare(
            'REPLACE INTO advertisers (id, organization_id, name, status, invoice_client_id, disabled_at)
             VALUES (?, ?, ?, ?, ?, ?)',
        );
        $stmt->execute([
            $advertiser->id,
            $advertiser->organizationId,
            $advertiser->name,
            $advertiser->status,
            $advertiser->invoiceClientId,
            $advertiser->disabledAt,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Advertiser
    {
        return new Advertiser(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['name'],
            (string) $row['status'],
            $row['invoice_client_id'] !== null ? (string) $row['invoice_client_id'] : null,
            $row['disabled_at'] !== null ? (string) $row['disabled_at'] : null,
        );
    }
}
