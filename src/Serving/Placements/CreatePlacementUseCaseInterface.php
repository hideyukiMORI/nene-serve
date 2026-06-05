<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use NeneServe\Serving\Placement;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Tenant\AuthContext;

interface CreatePlacementUseCaseInterface
{
    /**
     * @param list<string> $allowedOrigins
     *
     * @throws CreativeValidationException
     */
    public function execute(
        AuthContext $actor,
        string $publicPlacementKey,
        array $allowedOrigins,
        ?string $defaultCreativeId = null,
        string $status = 'draft',
    ): Placement;
}
