<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Mcp\Api\ProposePlacementChangeUseCaseInterface;
use NeneServe\Service\Auth\ServiceContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/delivery-plan-changes (operationId `proposeDeliveryPlanChange`,
 * service surface). Requires the `write:delivery_plan` scope. Returns a plan +
 * confirmation token; nothing changes until it is applied (api-security §5).
 */
final readonly class ProposeChangeHandler
{
    public function __construct(
        private ProposePlacementChangeUseCaseInterface $propose,
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

        $body = JsonRequestBodyParser::parse($request);

        $placementId = isset($body['placement_id']) && is_string($body['placement_id']) ? $body['placement_id'] : null;
        $newCreativeId = isset($body['new_creative_id']) && is_string($body['new_creative_id']) ? $body['new_creative_id'] : null;

        if ($placementId === null || $newCreativeId === null) {
            throw new ValidationException([new ValidationError('placement_id', 'placement_id and new_creative_id are required.', 'required')]);
        }

        $plan = $this->propose->execute($context, $placementId, $newCreativeId);

        return $this->response->create($plan->toArray(), 201);
    }
}
