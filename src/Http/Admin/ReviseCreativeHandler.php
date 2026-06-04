<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Serving\UseCase\ReviseCreativeUseCase;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/creatives/{id}/revise (operationId `reviseCreative`). Creates a
 * new draft version of an approved creative for re-review (immutability, §0.3).
 * Requires `manage_creatives`.
 */
final class ReviseCreativeHandler
{
    public function __construct(
        private readonly ReviseCreativeUseCase $revise,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $destination = $body['destination_url'] ?? null;
        $asset = $body['asset_url'] ?? null;
        $width = $body['width'] ?? null;
        $height = $body['height'] ?? null;
        if (!is_string($destination) || !is_string($asset) || !is_int($width) || !is_int($height)) {
            return $this->json->problem(
                422,
                'validation-failed',
                'destination_url, asset_url (string) and width, height (int) are required',
            );
        }

        try {
            $creative = $this->revise->execute($context, (string) $request->param('id'), $destination, $asset, $width, $height);
        } catch (CreativeNotFoundException) {
            return $this->json->problem(404, 'creative-not-found', 'Creative not found');
        } catch (InvalidReviewTransitionException $e) {
            return $this->json->problem(409, 'invalid-review-transition', 'Cannot revise', $e->getMessage());
        } catch (CreativeValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Image rejected', $e->getMessage());
        }

        return $this->json->ok($creative->toAdminArray(), 201);
    }
}
