<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

/**
 * Fine-grained admin capabilities (ADR 0006 / ADR 0018). Routes require a
 * Capability; roles are mapped to capabilities by {@see Role::capabilities()}.
 *
 * Registered in docs/explanation/terminology.md (Capabilities). Add new cases
 * there in the same PR.
 */
enum Capability: string
{
    case ViewUsers = 'view_users';
    case ManageUsers = 'manage_users';
    case ViewMetrics = 'view_metrics';
    case ManageSettings = 'manage_settings';
    case ReviewCreatives = 'review_creatives';
}
