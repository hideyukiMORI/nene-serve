<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** POST /admin/placements (operationId `createPlacement`). Requires `manage_placements`. */
final readonly class CreatePlacementHandler
{
    public function __construct(
        private CreatePlacementUseCaseInterface $createPlacement,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $body = JsonRequestBodyParser::parse($request);

        $key = isset($body['public_placement_key']) && is_string($body['public_placement_key'])
            ? $body['public_placement_key']
            : '';

        if ($key === '') {
            throw new ValidationException([new ValidationError('public_placement_key', 'A placement key is required.', 'required')]);
        }

        $origins = [];
        if (isset($body['allowed_origins']) && is_array($body['allowed_origins'])) {
            $origins = array_values(array_filter($body['allowed_origins'], 'is_string'));
        }
        $default = isset($body['default_creative_id']) && is_string($body['default_creative_id']) ? $body['default_creative_id'] : null;
        $status = isset($body['status']) && is_string($body['status']) ? $body['status'] : 'draft';

        $output = $this->createPlacement->execute(
            new CreatePlacementInput($context->userId, $key, $origins, $default, $status),
        );

        return $this->response->create($output->placement->toAdminArray(), 201);
    }
}
