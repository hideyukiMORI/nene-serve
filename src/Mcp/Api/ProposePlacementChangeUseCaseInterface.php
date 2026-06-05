<?php

declare(strict_types=1);

namespace NeneServe\Mcp\Api;

use NeneServe\Mcp\ChangePlan;
use NeneServe\Service\ServiceContext;

interface ProposePlacementChangeUseCaseInterface
{
    public function execute(ServiceContext $context, string $placementId, string $newCreativeId): ChangePlan;
}
