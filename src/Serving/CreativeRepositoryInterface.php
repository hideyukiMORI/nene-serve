<?php

declare(strict_types=1);

namespace NeneServe\Serving;

interface CreativeRepositoryInterface
{
    /** Serve-path lookup; tenant-scoped by the owning placement's organization. */
    public function findByIdInOrganization(string $id, string $organizationId): ?Creative;
}
