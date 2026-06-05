<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

interface RecordConversionUseCaseInterface
{
    public function execute(string $publicPlacementKey, ?string $creativeId = null, ?string $countryCode = null): void;
}
