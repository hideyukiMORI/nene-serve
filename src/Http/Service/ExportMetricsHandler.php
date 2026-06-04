<?php

declare(strict_types=1);

namespace NeneServe\Http\Service;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\MetricsRange;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use NeneServe\Service\ServiceContext;

/**
 * GET /api/metrics/export?from=&to= (operationId `exportMetrics`, service surface).
 * Requires the `read:metrics` scope. Aggregated, tenant-scoped CSV only (privacy
 * N8): MCP/automation never receives raw visitor identifiers.
 */
final class ExportMetricsHandler
{
    public function __construct(
        private readonly ExportMetricsUseCase $export,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, ServiceContext $context): Response
    {
        $range = MetricsRange::fromQuery($request);
        if ($range === null) {
            return $this->json->problem(422, 'validation-failed', 'from/to must be YYYY-MM-DD dates');
        }

        return new Response(200, $this->export->csv($context->organizationId, $range[0], $range[1]), [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
