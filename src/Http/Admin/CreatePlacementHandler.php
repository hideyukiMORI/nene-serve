<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\UseCase\CreatePlacementUseCase;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Tenant\AuthContext;

/** POST /admin/placements (operationId `createPlacement`). Requires `manage_placements`. */
final class CreatePlacementHandler
{
    public function __construct(
        private readonly CreatePlacementUseCase $createPlacement,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $key = $body['public_placement_key'] ?? null;
        if (!is_string($key) || $key === '') {
            return $this->json->problem(422, 'validation-failed', 'public_placement_key is required');
        }

        $origins = [];
        if (isset($body['allowed_origins']) && is_array($body['allowed_origins'])) {
            $origins = array_values(array_filter($body['allowed_origins'], 'is_string'));
        }
        $default = is_string($body['default_creative_id'] ?? null) ? $body['default_creative_id'] : null;
        $status = is_string($body['status'] ?? null) ? $body['status'] : 'draft';

        try {
            $placement = $this->createPlacement->execute($context, $key, $origins, $default, $status);
        } catch (CreativeValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid placement', $e->getMessage());
        }

        return $this->json->ok([
            'id' => $placement->id,
            'public_placement_key' => $placement->publicPlacementKey,
            'allowed_origins' => $placement->allowedOrigins,
            'status' => $placement->status,
            'default_creative_id' => $placement->defaultCreativeId,
        ], 201);
    }
}
