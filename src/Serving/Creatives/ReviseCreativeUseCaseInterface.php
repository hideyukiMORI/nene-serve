<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\Creative;
use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Tenant\AuthContext;

interface ReviseCreativeUseCaseInterface
{
    /**
     * @throws CreativeNotFoundException
     * @throws InvalidReviewTransitionException
     * @throws CreativeValidationException
     */
    public function execute(
        AuthContext $actor,
        string $creativeId,
        string $destinationUrl,
        string $assetUrl,
        int $width,
        int $height,
    ): Creative;
}
