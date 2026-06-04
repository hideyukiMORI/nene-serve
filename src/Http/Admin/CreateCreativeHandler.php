<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\UseCase\CreateImageCreativeUseCase;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/creatives (operationId `createCreative`). Requires
 * `manage_creatives`. MVP creates image creatives in `draft`; video/html5 land
 * in Phase 2 with scanning + sandbox (ADR 0021).
 */
final class CreateCreativeHandler
{
    public function __construct(
        private readonly CreateImageCreativeUseCase $createImage,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $type = $body['type'] ?? 'image';
        if ($type !== 'image') {
            return $this->json->problem(422, 'validation-failed', 'Only image creatives are supported in MVP (ADR 0021)');
        }

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
            $creative = $this->createImage->execute($context, $destination, $asset, $width, $height);
        } catch (CreativeValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Image rejected', $e->getMessage());
        }

        return $this->json->ok($creative->toAdminArray(), 201);
    }
}
