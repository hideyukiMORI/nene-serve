<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Metrics;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * GET /admin/metrics/export?from=&to= (operationId `exportMetrics`). Requires
 * `view_metrics`. Returns tenant-scoped aggregated CSV — no visitor identifiers
 * (privacy N8).
 */
final readonly class ExportMetricsHandler
{
    public function __construct(
        private ExportMetricsUseCase $export,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $range = MetricsRange::fromRequest($request);

        if ($range === null) {
            throw new ValidationException([new ValidationError('from', 'from/to must be YYYY-MM-DD dates.', 'invalid')]);
        }

        $csv = $this->export->csv($range[0], $range[1]);

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="metrics.csv"')
            ->withHeader('Cache-Control', 'no-store')
            ->withBody($this->streamFactory->createStream($csv));
    }
}
