<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/campaigns/{id} (operationId `getCampaign`). Requires
 * `manage_marketplace`. Returns the campaign plus its derived, reproducible
 * spend; aggregate money, no visitor identifiers.
 */
final readonly class GetCampaignHandler
{
    public function __construct(
        private CampaignRepositoryInterface $campaigns,
        private GetCampaignSpendUseCase $spend,
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

        $campaign = $this->campaigns->findByIdInOrganization($id, $context->organizationId);

        if ($campaign === null) {
            return $this->problemDetails->create($request, 'campaign-not-found', 'Campaign not found', 404, 'No campaign with that id.');
        }

        return $this->response->create($campaign->toArray() + ['spend' => $this->spend->forCampaign($campaign)->toArray()]);
    }
}
