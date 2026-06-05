<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $id = Router::param($request, 'id') ?? '';

        $hold = $this->legalHolds->release(new ReleaseLegalHoldInput($context->userId, $id))->hold;

        return $this->response->create($hold->toArray());
    }
}
