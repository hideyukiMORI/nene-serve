<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Auth;

use NeneServe\Tenant\Capability;

/**
 * Resolves the {@see Capability} required to call an admin route, replacing the
 * per-route `admin(Capability, ...)` wrapper of the legacy kernel. Returns
 * `null` when a route only requires an authenticated session (e.g. `/admin/me`).
 *
 * The table grows as each admin domain is ported to the NENE2 runtime (Phase 2).
 */
final class CapabilityResolver
{
    public static function resolve(string $path, string $method): ?Capability
    {
        $method = strtoupper($method);

        if (str_starts_with($path, '/admin/users')) {
            return match ($method) {
                'GET', 'HEAD' => Capability::ViewUsers,
                default => Capability::ManageUsers,
            };
        }

        if (str_starts_with($path, '/admin/metrics')) {
            return Capability::ViewMetrics;
        }

        if (
            str_starts_with($path, '/admin/settings')
            || str_starts_with($path, '/admin/data-subject-requests')
            || str_starts_with($path, '/admin/legal-holds')
        ) {
            return Capability::ManageSettings;
        }

        if (str_starts_with($path, '/admin/placements')) {
            return Capability::ManagePlacements;
        }

        if (str_starts_with($path, '/admin/review-queue')) {
            return Capability::ReviewCreatives;
        }

        if (str_starts_with($path, '/admin/creatives')) {
            // Four-eyes: reviewer decisions need review_creatives; author actions
            // (create, submit, revise) need manage_creatives (ADR 0020 §4).
            foreach (['/start-review', '/approve', '/reject', '/request-changes'] as $reviewerAction) {
                if (str_ends_with($path, $reviewerAction)) {
                    return Capability::ReviewCreatives;
                }
            }

            return Capability::ManageCreatives;
        }

        if (str_starts_with($path, '/admin/assets') || str_starts_with($path, '/admin/records-assets')) {
            return Capability::ManageCreatives;
        }

        if (
            str_starts_with($path, '/admin/advertisers')
            || str_starts_with($path, '/admin/pricing-rules')
            || str_starts_with($path, '/admin/campaigns')
            || str_starts_with($path, '/admin/billing-periods')
        ) {
            return Capability::ManageMarketplace;
        }

        return null;
    }
}
