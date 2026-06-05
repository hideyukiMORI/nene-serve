<?php

declare(strict_types=1);

namespace NeneServe\Service\Auth;

use NeneServe\Service\Scope;

/**
 * Maps a service-surface route (path + method) to the scope it requires
 * (api-security §5). An unmapped `/api/*` route resolves to null and is left to
 * the handler; the surface itself is already token-gated by
 * {@see ServiceAuthMiddleware}.
 */
final class ScopeResolver
{
    public static function resolve(string $path, string $method): ?Scope
    {
        if ($path === '/api/placements') {
            return Scope::ReadPlacements;
        }

        if ($path === '/api/metrics' || $path === '/api/metrics/export') {
            return Scope::ReadMetrics;
        }

        if (str_starts_with($path, '/api/delivery-plan-changes')) {
            return Scope::WriteDeliveryPlan;
        }

        return null;
    }
}
