<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/campaigns (operationId `listCampaigns`). Requires `manage_marketplace`; tenant-scoped. */
final readonly class ListCampaignsHandler
{
    public function __construct(
        private CampaignRepositoryInterface $campaigns,
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

        return $this->response->create([
            'campaigns' => array_map(
                static fn ($campaign) => $campaign->toArray(),
                $this->campaigns->listByOrganization($context->organizationId),
            ),
        ]);
    }
}
