<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

interface UploadAssetUseCaseInterface
{
    public function execute(UploadAssetInput $input): UploadAssetOutput;
}
