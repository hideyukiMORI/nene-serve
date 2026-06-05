<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Mcp\Api\ApplyChangePlanUseCaseInterface;
use NeneServe\Service\Auth\ServiceContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/delivery-plan-changes/{token}/apply (operationId
 * `applyDeliveryPlanChange`, service surface). Requires `write:delivery_plan`.
 * Applies the confirmed plan; audited; a non-proposed plan → 409.
 */
final readonly class ApplyChangeHandler
{
    public function __construct(
        private ApplyChangePlanUseCaseInterface $apply,
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

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $token = is_array($parameters) && is_string($parameters['token'] ?? null) ? $parameters['token'] : '';

        $plan = $this->apply->execute($context, $token);

        return $this->response->create($plan->toArray());
    }
}
