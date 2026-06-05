<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\Creative;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Tenant\AuthContext;

interface CreateCreativeUseCaseInterface
{
    /** @throws CreativeValidationException */
    public function createImage(AuthContext $actor, string $destinationUrl, string $assetUrl, int $width, int $height, ?string $campaignId = null): Creative;

    /** @throws CreativeValidationException */
    public function createVideo(AuthContext $actor, string $destinationUrl, string $assetUrl, string $posterUrl, int $width, int $height, int $durationSeconds, ?string $campaignId = null): Creative;

    /** @throws CreativeValidationException */
    public function createHtml5(AuthContext $actor, string $destinationUrl, string $bundleId, int $bundleSizeBytes, int $assetCount, string $htmlEntry, ?int $width = null, ?int $height = null, ?string $campaignId = null): Creative;
}
