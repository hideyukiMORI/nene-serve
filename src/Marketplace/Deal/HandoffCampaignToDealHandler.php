<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/campaigns/{id}/deal-handoff (operationId `handoffCampaignToDeal`).
 * Requires `manage_marketplace`. Idempotent; a transport failure (502) does not
 * affect serving and is retryable.
 */
final readonly class HandoffCampaignToDealHandler
{
    public function __construct(
        private HandoffCampaignToDealUseCaseInterface $handoff,
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

        $opportunity = $this->handoff->execute(new HandoffCampaignToDealInput($context->userId, $id))->opportunity;

        return $this->response->create($opportunity->toArray());
    }
}
