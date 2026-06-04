<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

use PDO;

final class PdoOrganizationRepository implements OrganizationRepositoryInterface
{
    private const COLUMNS = 'id, slug, name, default_locale, status';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findById(string $id): ?Organization
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM organizations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $this->hydrate($stmt->fetch());
    }

    public function findBySlug(string $slug): ?Organization
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM organizations WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);

        return $this->hydrate($stmt->fetch());
    }

    /** @param array<string, mixed>|false $row */
    private function hydrate(array|false $row): ?Organization
    {
        if ($row === false) {
            return null;
        }

        return new Organization(
            (string) $row['id'],
            (string) $row['slug'],
            (string) $row['name'],
            (string) $row['default_locale'],
            (string) $row['status'],
        );
    }
}
