<?php

declare(strict_types=1);

namespace NeneServe\Serving\Review;

use NeneServe\Serving\DestinationUrl;
use NeneServe\Serving\UseCase\CreativeValidationException;

/**
 * Video creative acceptance rules (ADR 0021 §3): HTTPS-hosted MP4/WebM, a
 * required poster image, bounded duration, and a safe registered destination.
 * Autoplay-with-sound is disabled at render time (see Creative::toServePayload).
 */
final class VideoAcceptance
{
    private const FORMATS = ['mp4', 'webm'];
    private const POSTER_FORMATS = ['png', 'jpg', 'jpeg', 'webp'];
    private const MAX_DURATION_SECONDS = 180;

    /**
     * @throws CreativeValidationException
     */
    public static function assertValid(string $assetUrl, string $posterUrl, string $destinationUrl, int $durationSeconds): void
    {
        if (!DestinationUrl::isSafe($assetUrl)) {
            throw new CreativeValidationException('asset_url must be https (or http on localhost).');
        }
        if (!in_array(self::extension($assetUrl), self::FORMATS, true)) {
            throw new CreativeValidationException('asset_url must be one of: ' . implode(', ', self::FORMATS) . '.');
        }

        if (!DestinationUrl::isSafe($posterUrl)) {
            throw new CreativeValidationException('poster_url is required and must be https (or http on localhost).');
        }
        if (!in_array(self::extension($posterUrl), self::POSTER_FORMATS, true)) {
            throw new CreativeValidationException('poster_url must be an image: ' . implode(', ', self::POSTER_FORMATS) . '.');
        }

        if ($durationSeconds <= 0 || $durationSeconds > self::MAX_DURATION_SECONDS) {
            throw new CreativeValidationException('duration_seconds must be between 1 and ' . self::MAX_DURATION_SECONDS . '.');
        }

        if (!DestinationUrl::isSafe($destinationUrl)) {
            throw new CreativeValidationException('destination_url must be https (or http on localhost) — no open redirect.');
        }
    }

    private static function extension(string $url): string
    {
        return strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }
}
