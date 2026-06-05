<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Metrics;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use NeneServe\Tenant\Capability;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/metrics?from=&to=[&include_sensitive=true] (operationId
 * `getPlacementMetrics`). Requires `view_metrics`. Aggregated, tenant-scoped JSON.
 *
 * `include_sensitive=true` adds the per-`visitor_bucket` breakdown — it requires
 * the `view_sensitive_metrics` capability and is **audited** (ADR 0022 §4).
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
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        $range = MetricsRange::fromRequest($request);

        if ($range === null) {
            throw new ValidationException([new ValidationError('from', 'from/to must be YYYY-MM-DD dates.', 'invalid')]);
        }

        $params = $request->getQueryParams();

        if (($params['include_sensitive'] ?? null) === 'true') {
            if (!$context->can(Capability::ViewSensitiveMetrics)) {
                return $this->problemDetails->create(
                    $request,
                    'forbidden',
                    'Forbidden',
                    403,
                    'Sensitive metrics require the view_sensitive_metrics capability.',
                );
            }

            return $this->response->create($this->metrics->sensitiveReport($context, $range[0], $range[1]));
        }

        return $this->response->create($this->metrics->report($context->organizationId, $range[0], $range[1]));
    }
}
