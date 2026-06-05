<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use NeneServe\Upstream\Records\RecordsClientException;

interface GetRecordsAssetUseCaseInterface
{
    /**
     * @throws RecordsClientException on transport failure
     */
    public function execute(GetRecordsAssetInput $input): GetRecordsAssetOutput;
}
