<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/advertisers (operationId `listAdvertisers`). Requires `manage_marketplace`; tenant-scoped. */
final readonly class ListAdvertisersHandler
{
    public function __construct(
        private AdvertiserRepositoryInterface $advertisers,
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
            'advertisers' => array_map(
                static fn ($advertiser) => $advertiser->toArray(),
                $this->advertisers->listByOrganization($context->organizationId),
            ),
        ]);
    }
}
