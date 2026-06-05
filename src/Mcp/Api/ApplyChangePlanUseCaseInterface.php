<?php

declare(strict_types=1);

namespace NeneServe\Mcp\Api;

use NeneServe\Mcp\ChangePlan;
use NeneServe\Service\ServiceContext;

interface ApplyChangePlanUseCaseInterface
{
    public function execute(ServiceContext $context, string $planId): ChangePlan;
}
