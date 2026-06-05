<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/creatives/{id}/revise (operationId `reviseCreative`). Requires
 * `manage_creatives`. Creates a new draft version of an approved creative.
 */
final readonly class ReviseCreativeHandler
{
    public function __construct(
        private ReviseCreativeUseCaseInterface $revise,
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

        $body = JsonRequestBodyParser::parse($request);

        $creative = $this->revise->execute(new ReviseCreativeInput(
            $context->userId,
            $id,
            $this->str($body, 'destination_url'),
            $this->str($body, 'asset_url'),
            $this->int($body, 'width'),
            $this->int($body, 'height'),
        ))->creative;

        return $this->response->create($creative->toAdminArray());
    }

    /** @param array<string, mixed> $body */
    private function str(array $body, string $key): string
    {
        if (!isset($body[$key]) || !is_string($body[$key]) || $body[$key] === '') {
            throw new ValidationException([new ValidationError($key, sprintf('%s is required.', $key), 'required')]);
        }

        return $body[$key];
    }

    /** @param array<string, mixed> $body */
    private function int(array $body, string $key): int
    {
        if (!isset($body[$key]) || !is_int($body[$key])) {
            throw new ValidationException([new ValidationError($key, sprintf('%s must be an integer.', $key), 'invalid')]);
        }

        return $body[$key];
    }
}
