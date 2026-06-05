<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use NeneServe\Serving\Token\ClickRedirect;

interface RecordClickUseCaseInterface
{
    public function execute(string $token, ?string $countryCode = null): ?ClickRedirect;
}
