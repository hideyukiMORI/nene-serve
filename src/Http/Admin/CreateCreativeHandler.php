<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\UseCase\CreateHtml5CreativeUseCase;
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
        private readonly CreateHtml5CreativeUseCase $createHtml5,
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
            'html5_bundle' => $this->html5($body, $context),
            default => $this->json->problem(422, 'validation-failed', 'Unsupported creative type; third_party_tag is forbidden (ADR 0013/0021)'),
        };
    }

    /** @param array<string, mixed> $body */
    private function html5(array $body, AuthContext $context): Response
    {
        $destination = $body['destination_url'] ?? null;
        $bundleId = $body['bundle_id'] ?? null;
        $size = $body['bundle_size_bytes'] ?? null;
        $assetCount = $body['asset_count'] ?? null;
        $htmlEntry = $body['html_entry'] ?? null;
        if (!is_string($destination) || !is_string($bundleId) || !is_int($size)
            || !is_int($assetCount) || !is_string($htmlEntry)) {
            return $this->json->problem(
                422,
                'validation-failed',
                'destination_url, bundle_id, html_entry (string) and bundle_size_bytes, asset_count (int) are required',
            );
        }

        $width = is_int($body['width'] ?? null) ? $body['width'] : null;
        $height = is_int($body['height'] ?? null) ? $body['height'] : null;

        try {
            $creative = $this->createHtml5->execute($context, $destination, $bundleId, $size, $assetCount, $htmlEntry, $width, $height, self::campaignId($body));
        } catch (CreativeValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'HTML5 bundle rejected', $e->getMessage());
        }

        return $this->json->ok($creative->toAdminArray(), 201);
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
            $creative = $this->createImage->execute($context, $destination, $asset, $width, $height, self::campaignId($body));
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
            $creative = $this->createVideo->execute($context, $destination, $asset, $poster, $width, $height, $duration, self::campaignId($body));
        } catch (CreativeValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Video rejected', $e->getMessage());
        }

        return $this->json->ok($creative->toAdminArray(), 201);
    }

    /** @param array<string, mixed> $body */
    private static function campaignId(array $body): ?string
    {
        return is_string($body['campaign_id'] ?? null) ? $body['campaign_id'] : null;
    }
}
