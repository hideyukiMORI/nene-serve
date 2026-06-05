<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

interface RecordImpressionUseCaseInterface
{
    public function execute(
        string $token,
        string $clientIp,
        string $userAgent,
        bool $consentGranted,
        ?string $countryCode = null,
        ?string $pageUrl = null,
    ): void;
}
