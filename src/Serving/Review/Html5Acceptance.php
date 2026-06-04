<?php

declare(strict_types=1);

namespace NeneServe\Serving\Review;

use NeneServe\Serving\DestinationUrl;
use NeneServe\Serving\UseCase\CreativeValidationException;

/**
 * HTML5 bundle acceptance (ADR 0021 §1/§4): bounded zip size and asset count, a
 * safe registered destination, plus content-policy checks on the entry document
 * — no raw remote `<script src>` (forbidden third-party tags), no `eval`, and no
 * top-level navigation. Malware scanning is separate (BundleScanner).
 */
final class Html5Acceptance
{
    private const MAX_SIZE_BYTES = 2_097_152; // 2 MiB
    private const MAX_ASSET_COUNT = 50;

    /**
     * @throws CreativeValidationException
     */
    public static function assertValid(int $sizeBytes, int $assetCount, string $destinationUrl, string $htmlEntry): void
    {
        if ($sizeBytes <= 0 || $sizeBytes > self::MAX_SIZE_BYTES) {
            throw new CreativeValidationException('bundle_size_bytes must be between 1 and ' . self::MAX_SIZE_BYTES . '.');
        }
        if ($assetCount <= 0 || $assetCount > self::MAX_ASSET_COUNT) {
            throw new CreativeValidationException('asset_count must be between 1 and ' . self::MAX_ASSET_COUNT . '.');
        }
        if (!DestinationUrl::isSafe($destinationUrl)) {
            throw new CreativeValidationException('destination_url must be https (or http on localhost) — no open redirect.');
        }

        self::assertContentSafe($htmlEntry);
    }

    /**
     * @throws CreativeValidationException
     */
    private static function assertContentSafe(string $html): void
    {
        $lower = strtolower($html);

        if (preg_match('/<script[^>]+src\s*=/', $lower) === 1) {
            throw new CreativeValidationException('Remote <script src> is forbidden (no third-party tags, ADR 0013/0021).');
        }
        if (str_contains($lower, 'eval(')) {
            throw new CreativeValidationException('eval() is forbidden in HTML5 bundles (ADR 0021 §4).');
        }
        foreach (['window.top', 'window.parent', 'top.location', "target=\"_top\"", "target='_top'"] as $needle) {
            if (str_contains($lower, strtolower($needle))) {
                throw new CreativeValidationException('Top-level navigation is forbidden; clicks must use the registered redirect.');
            }
        }
    }
}
