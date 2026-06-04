<?php

declare(strict_types=1);

namespace NeneServe\Serving\Review;

use NeneServe\Serving\DestinationUrl;
use NeneServe\Serving\UseCase\CreativeValidationException;

/**
 * Image creative acceptance rules (ADR 0021 §3): HTTPS asset, format allowlist,
 * bounded dimensions, and a safe registered destination. Byte-size and a hosted
 * upload path land with the asset-storage work; this gates URL-referenced images.
 */
final class ImageAcceptance
{
    private const FORMATS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    private const MAX_DIMENSION = 2000;

    /**
     * @throws CreativeValidationException
     */
    public static function assertValid(string $assetUrl, string $destinationUrl, ?int $width, ?int $height): void
    {
        if (!DestinationUrl::isSafe($assetUrl)) {
            throw new CreativeValidationException('asset_url must be https (or http on localhost).');
        }

        $path = (string) parse_url($assetUrl, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, self::FORMATS, true)) {
            throw new CreativeValidationException(
                'asset_url must be one of: ' . implode(', ', self::FORMATS) . '.',
            );
        }

        if ($width === null || $height === null || $width <= 0 || $height <= 0) {
            throw new CreativeValidationException('width and height are required and must be positive.');
        }
        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw new CreativeValidationException('width/height exceed the ' . self::MAX_DIMENSION . 'px maximum.');
        }

        if (!DestinationUrl::isSafe($destinationUrl)) {
            throw new CreativeValidationException('destination_url must be https (or http on localhost) — no open redirect.');
        }
    }
}
