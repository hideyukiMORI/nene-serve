<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\SqlDialect;

final readonly class PdoAdvertiserRepository implements AdvertiserRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, name, status, invoice_client_id, disabled_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Advertiser
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM advertisers WHERE id = ? AND organization_id = ? LIMIT 1',
            [$id, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM advertisers WHERE organization_id = ? ORDER BY name LIMIT ? OFFSET ?',
            [$organizationId, $limit, $offset],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function save(Advertiser $advertiser): void
    {
        $this->query->execute(
            $this->dialect->upsert(
                'advertisers',
                ['id', 'organization_id', 'name', 'status', 'invoice_client_id', 'disabled_at'],
                ['id'],
                ['name', 'status', 'invoice_client_id', 'disabled_at'],
            ),
            [
                $advertiser->id,
                $advertiser->organizationId,
                $advertiser->name,
                $advertiser->status,
                $advertiser->invoiceClientId,
                $advertiser->disabledAt,
            ],
        );
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
