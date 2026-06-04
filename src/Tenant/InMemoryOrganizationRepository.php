<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

final class InMemoryOrganizationRepository implements OrganizationRepositoryInterface
{
    /** @var list<Organization> */
    private array $organizations;

    /** @param list<Organization> $organizations */
    public function __construct(array $organizations = [])
    {
        $this->organizations = $organizations;
    }

    public function findById(string $id): ?Organization
    {
        foreach ($this->organizations as $org) {
            if ($org->id === $id) {
                return $org;
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?Organization
    {
        foreach ($this->organizations as $org) {
            if ($org->slug === $slug) {
                return $org;
            }
        }

        return null;
    }
}
