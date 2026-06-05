<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\Review\ReviewAction;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/creatives/{id}/{action} for one fixed review action. The
 * state-machine rules live in the use case; domain exceptions
 * (not-found/invalid-transition/self-approval/scan) map to HTTP via registered
 * DomainExceptionHandlers.
 */
final readonly class TransitionCreativeHandler
{
    public function __construct(
        private ReviewAction $action,
        private TransitionCreativeUseCaseInterface $transition,
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

        $id = Router::param($request, 'id') ?? '';

        $body = $request->getBody()->getSize() > 0 ? JsonRequestBodyParser::parse($request) : [];
        $reason = isset($body['review_reason']) && is_string($body['review_reason']) ? $body['review_reason'] : null;
        $override = ($body['self_approval_override'] ?? false) === true;

        $creative = $this->transition->execute(
            new TransitionCreativeInput($context->userId, $id, $this->action, $reason, $override),
        )->creative;

        return $this->response->create($creative->toAdminArray());
    }
}
