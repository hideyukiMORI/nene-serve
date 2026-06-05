<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Measurement\Metrics\GetMetricsUseCase;
use NeneServe\Measurement\Metrics\MetricsRange;
use NeneServe\Service\Auth\ServiceContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/metrics?from=&to= (operationId `getPlacementMetrics`, service surface).
 * Requires the `read:metrics` scope. Aggregated, tenant-scoped JSON only (privacy
 * N8): MCP/automation never receives raw visitor identifiers.
 */
final readonly class GetMetricsHandler
{
    public function __construct(
        private GetMetricsUseCase $metrics,
        private JsonResponseFactory $response,
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

        return $this->response->create($this->metrics->report($range[0], $range[1]));
    }
}
