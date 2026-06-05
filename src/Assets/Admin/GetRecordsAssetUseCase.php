<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use NeneServe\Upstream\Records\RecordsClientInterface;

final readonly class GetRecordsAssetUseCase implements GetRecordsAssetUseCaseInterface
{
    public function __construct(
        private RecordsClientInterface $records,
    ) {
    }

    public function execute(GetRecordsAssetInput $input): GetRecordsAssetOutput
    {
        return new GetRecordsAssetOutput($this->records->fetchAsset($input->ref));
    }
}
