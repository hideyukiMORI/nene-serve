<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\MetricsRange;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\GetMetricsUseCase;
use NeneServe\Tenant\AuthContext;

/**
 * GET /admin/metrics?from=&to= (operationId `getPlacementMetrics`). Requires
 * `view_metrics`. Tenant-scoped JSON time-series (impressions/clicks/CTR + fill
 * rate); aggregated only — no visitor identifiers (privacy N8).
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

        return $this->json->ok($this->metrics->report($context->organizationId, $range[0], $range[1]));
    }
}
