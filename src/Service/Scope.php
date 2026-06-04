<?php

declare(strict_types=1);

namespace NeneServe\Service;

/**
 * Service-token scopes (api-security §5). Service tokens grant explicit scopes,
 * not human capabilities; MCP is read-first. Registered in terminology.md.
 */
enum Scope: string
{
    case ReadPlacements = 'read:placements';
    case ReadMetrics = 'read:metrics';
    case WriteDeliveryPlan = 'write:delivery_plan';
}
