<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/creatives/{id} (operationId `getCreativeById`). Requires `manage_creatives`; tenant-scoped. */
final readonly class GetCreativeHandler
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

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        $creative = $this->creatives->findByIdInOrganization($id, $context->organizationId);

        if ($creative === null) {
            return $this->problemDetails->create($request, 'creative-not-found', 'Creative not found', 404, 'No creative with that id.');
        }

        return $this->response->create($creative->toAdminArray());
    }
}
