<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/creatives (operationId `listCreatives`). Requires `manage_creatives`; tenant-scoped. */
final readonly class ListCreativesHandler
{
    public function __construct(
        private CreativeRepositoryInterface $creatives,
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

        $creatives = array_map(
            static fn ($creative) => $creative->toAdminArray(),
            $this->creatives->listByOrganization($context->organizationId),
        );

        return $this->response->create(['creatives' => $creatives]);
    }
}
