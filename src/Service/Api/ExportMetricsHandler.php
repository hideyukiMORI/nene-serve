<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneServe\Measurement\Metrics\MetricsRange;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use NeneServe\Service\Auth\ServiceContextResolver;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * GET /api/metrics/export?from=&to= (operationId `exportMetrics`, service surface).
 * Requires the `read:metrics` scope. Aggregated, tenant-scoped CSV only (privacy
 * N8): MCP/automation never receives raw visitor identifiers.
 */
final readonly class ExportMetricsHandler
{
    public function __construct(
        private ExportMetricsUseCase $export,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = ServiceContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'A service token is required.');
        }

        $range = MetricsRange::fromRequest($request);

        if ($range === null) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation failed', 422, 'from/to must be YYYY-MM-DD dates.');
        }

        $csv = $this->export->csv($range[0], $range[1]);

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withBody($this->streamFactory->createStream($csv));
    }
}
