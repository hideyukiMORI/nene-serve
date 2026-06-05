<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use NeneServe\Assets\Asset;
use NeneServe\Tenant\AuthContext;

interface UploadAssetUseCaseInterface
{
    public function execute(AuthContext $actor, string $contentType, string $bytes): Asset;
}
