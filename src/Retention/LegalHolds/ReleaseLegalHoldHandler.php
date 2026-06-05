<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/legal-holds/{id}/release (operationId `releaseLegalHold`). Requires
 * `manage_settings`. Audited; the hold is tombstoned (released_at), never deleted.
 */
final readonly class ReleaseLegalHoldHandler
{
    public function __construct(
        private LegalHoldUseCaseInterface $legalHolds,
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

        $hold = $this->legalHolds->release(new ReleaseLegalHoldInput($context->userId, $id))->hold;

        return $this->response->create($hold->toArray());
    }
}
