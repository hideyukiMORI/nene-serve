<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\UseCase\CreateImageCreativeUseCase;
use NeneServe\Serving\UseCase\CreateVideoCreativeUseCase;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/creatives (operationId `createCreative`). Requires
 * `manage_creatives`. Supports `image` and `video` (ADR 0021 §3); both start in
 * `draft` and walk the review state machine. `html5_bundle` lands in #25;
 * `third_party_tag` is forbidden.
 */
final class CreateCreativeHandler
{
    public function __construct(
        private readonly CreateImageCreativeUseCase $createImage,
        private readonly CreateVideoCreativeUseCase $createVideo,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $type = $body['type'] ?? 'image';

        return match ($type) {
            'image' => $this->image($body, $context),
            'video' => $this->video($body, $context),
            default => $this->json->problem(422, 'validation-failed', 'Only image and video creatives are supported (ADR 0021); html5 is #25'),
        };
    }

    /** @param array<string, mixed> $body */
    private function image(array $body, AuthContext $context): Response
    {
        $destination = $body['destination_url'] ?? null;
        $asset = $body['asset_url'] ?? null;
        $width = $body['width'] ?? null;
        $height = $body['height'] ?? null;
        if (!is_string($destination) || !is_string($asset) || !is_int($width) || !is_int($height)) {
            return $this->json->problem(422, 'validation-failed', 'destination_url, asset_url (string) and width, height (int) are required');
        }

        try {
            $creative = $this->createImage->execute($context, $destination, $asset, $width, $height);
        } catch (CreativeValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Image rejected', $e->getMessage());
        }

        return $this->json->ok($creative->toAdminArray(), 201);
    }

    /** @param array<string, mixed> $body */
    private function video(array $body, AuthContext $context): Response
    {
        $destination = $body['destination_url'] ?? null;
        $asset = $body['asset_url'] ?? null;
        $poster = $body['poster_url'] ?? null;
        $width = $body['width'] ?? null;
        $height = $body['height'] ?? null;
        $duration = $body['duration_seconds'] ?? null;
        if (!is_string($destination) || !is_string($asset) || !is_string($poster)
            || !is_int($width) || !is_int($height) || !is_int($duration)) {
            return $this->json->problem(
                422,
                'validation-failed',
                'destination_url, asset_url, poster_url (string) and width, height, duration_seconds (int) are required',
            );
        }

        try {
            $creative = $this->createVideo->execute($context, $destination, $asset, $poster, $width, $height, $duration);
        } catch (CreativeValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Video rejected', $e->getMessage());
        }

        return $this->json->ok($creative->toAdminArray(), 201);
    }
}
