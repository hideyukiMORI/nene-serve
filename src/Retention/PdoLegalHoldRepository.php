<?php

declare(strict_types=1);

namespace NeneServe\Retention;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\SqlDialect;

final readonly class PdoLegalHoldRepository implements LegalHoldRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, reason, placed_at, released_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?LegalHold
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM legal_holds WHERE id = ? AND organization_id = ? LIMIT 1',
            [$id, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function hasActiveHold(string $organizationId): bool
    {
        $row = $this->query->fetchOne(
            'SELECT 1 AS active FROM legal_holds WHERE organization_id = ? AND released_at IS NULL LIMIT 1',
            [$organizationId],
        );

        return $row !== null;
    }

    public function save(LegalHold $hold): void
    {
        $this->query->execute(
            $this->dialect->upsert(
                'legal_holds',
                ['id', 'organization_id', 'reason', 'placed_at', 'released_at'],
                ['id'],
                ['reason', 'placed_at', 'released_at'],
            ),
            [$hold->id, $hold->organizationId, $hold->reason, $hold->placedAt, $hold->releasedAt],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): LegalHold
    {
        return new LegalHold(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['reason'],
            (string) $row['placed_at'],
            $row['released_at'] !== null ? (string) $row['released_at'] : null,
        );
    }
}
