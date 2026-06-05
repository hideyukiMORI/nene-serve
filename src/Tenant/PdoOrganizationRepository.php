<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoOrganizationRepository implements OrganizationRepositoryInterface
{
    private const COLUMNS = 'id, slug, name, default_locale, status';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(string $id): ?Organization
    {
        $row = $this->query->fetchOne('SELECT ' . self::COLUMNS . ' FROM organizations WHERE id = ? LIMIT 1', [$id]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function findBySlug(string $slug): ?Organization
    {
        $row = $this->query->fetchOne('SELECT ' . self::COLUMNS . ' FROM organizations WHERE slug = ? LIMIT 1', [$slug]);

        return $row === null ? null : $this->hydrate($row);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Organization
    {
        return new Organization(
            (string) $row['id'],
            (string) $row['slug'],
            (string) $row['name'],
            (string) $row['default_locale'],
            (string) $row['status'],
        );
    }
}
