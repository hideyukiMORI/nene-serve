<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\MetricsRange;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\GetMetricsUseCase;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\Capability;

/**
 * GET /admin/metrics?from=&to=[&include_sensitive=true] (operationId
 * `getPlacementMetrics`). Requires `view_metrics`. Aggregated, tenant-scoped JSON.
 *
 * `include_sensitive=true` adds the per-`visitor_bucket` breakdown — it requires
 * the `view_sensitive_metrics` capability and is **audited** (ADR 0022 §4);
 * ordinary aggregate reads are not audited (privacy N8).
 */
final class GetMetricsHandler
{
    public function __construct(
        private readonly GetMetricsUseCase $metrics,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $range = MetricsRange::fromQuery($request);
        if ($range === null) {
            return $this->json->problem(422, 'validation-failed', 'from/to must be YYYY-MM-DD dates');
        }

        if (($request->query['include_sensitive'] ?? null) === 'true') {
            if (!$context->can(Capability::ViewSensitiveMetrics)) {
                return $this->json->problem(
                    403,
                    'insufficient-capability',
                    'Insufficient capability',
                    'Sensitive metrics require the view_sensitive_metrics capability.',
                );
            }

            return $this->json->ok($this->metrics->sensitiveReport($context, $range[0], $range[1]));
        }

        return $this->json->ok($this->metrics->report($context->organizationId, $range[0], $range[1]));
    }
}
