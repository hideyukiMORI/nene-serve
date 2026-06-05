<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/legal-holds (operationId `placeLegalHold`). Requires
 * `manage_settings`. While any hold is active, retention purges are blocked
 * (billing §7, ADR 0022 §7). Audited.
 */
final readonly class PlaceLegalHoldHandler
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

        $body = JsonRequestBodyParser::parse($request);
        $reason = isset($body['reason']) && is_string($body['reason']) ? $body['reason'] : '';

        if ($reason === '') {
            throw new ValidationException([new ValidationError('reason', 'A reason is required.', 'required')]);
        }

        $hold = $this->legalHolds->place(new PlaceLegalHoldInput($context->userId, $reason))->hold;

        return $this->response->create($hold->toArray(), 201);
    }
}
