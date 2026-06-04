<?php

declare(strict_types=1);

namespace NeneServe\Retention;

use PDO;

final class PdoLegalHoldRepository implements LegalHoldRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, reason, placed_at, released_at';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?LegalHold
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM legal_holds WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function hasActiveHold(string $organizationId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM legal_holds WHERE organization_id = ? AND released_at IS NULL LIMIT 1');
        $stmt->execute([$organizationId]);

        return $stmt->fetchColumn() !== false;
    }

    public function save(LegalHold $hold): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO legal_holds (id, organization_id, reason, placed_at, released_at)
             VALUES (?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE reason = new.reason, placed_at = new.placed_at, released_at = new.released_at',
        );
        $stmt->execute([$hold->id, $hold->organizationId, $hold->reason, $hold->placedAt, $hold->releasedAt]);
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
