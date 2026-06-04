<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

interface OrganizationRepositoryInterface
{
    public function findById(string $id): ?Organization;

    public function findBySlug(string $slug): ?Organization;
}
