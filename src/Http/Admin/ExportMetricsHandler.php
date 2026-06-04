<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\MetricsRange;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use NeneServe\Tenant\AuthContext;

/**
 * GET /admin/metrics/export?from=&to= (operationId `exportMetrics`). Requires
 * `view_metrics` (analyst is read-only metrics). Returns tenant-scoped aggregated
 * CSV — no visitor identifiers (privacy N8).
 */
final class ExportMetricsHandler
{
    public function __construct(
        private readonly ExportMetricsUseCase $export,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $range = MetricsRange::fromQuery($request);
        if ($range === null) {
            return $this->json->problem(422, 'validation-failed', 'from/to must be YYYY-MM-DD dates');
        }

        $csv = $this->export->csv($context->organizationId, $range[0], $range[1]);

        return new Response(200, $csv, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="metrics.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
