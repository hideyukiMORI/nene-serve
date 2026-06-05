<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use NeneServe\Serving\UseCase\ServeResult;

interface ServeCreativeUseCaseInterface
{
    public function execute(
        string $publicPlacementKey,
        ?string $origin,
        bool $consentGranted = false,
        string $clientIp = '',
        string $userAgent = '',
    ): ServeResult;
}
