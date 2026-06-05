<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/billing-periods/{id}/handoff (operationId `handoffBillingPeriod`).
 * Requires `manage_marketplace`. Reconciles the closed period and hands the net
 * amount to Invoice; idempotent on external_reference. A transport failure (502)
 * does not pause serving and is safe to retry.
 */
final readonly class HandoffBillingPeriodHandler
{
    public function __construct(
        private HandoffBillingPeriodUseCaseInterface $handoff,
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

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        $record = $this->handoff->execute(new HandoffBillingPeriodInput($context->userId, $id))->handoff;

        return $this->response->create($record->toArray());
    }
}
