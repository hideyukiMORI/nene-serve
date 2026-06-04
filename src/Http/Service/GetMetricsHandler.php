<?php

declare(strict_types=1);

namespace NeneServe\Http\Service;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\MetricsRange;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\GetMetricsUseCase;
use NeneServe\Service\ServiceContext;

/**
 * GET /api/metrics?from=&to= (operationId `getPlacementMetrics`, service surface).
 * Requires the `read:metrics` scope. Aggregated, tenant-scoped JSON only (N8).
 */
final class GetMetricsHandler
{
    public function __construct(
        private readonly GetMetricsUseCase $metrics,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, ServiceContext $context): Response
    {
        $range = MetricsRange::fromQuery($request);
        if ($range === null) {
            return $this->json->problem(422, 'validation-failed', 'from/to must be YYYY-MM-DD dates');
        }

        return $this->json->ok($this->metrics->report($context->organizationId, $range[0], $range[1]));
    }
}
