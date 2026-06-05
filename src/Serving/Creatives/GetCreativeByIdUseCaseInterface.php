<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\UseCase\CreativeNotFoundException;

interface GetCreativeByIdUseCaseInterface
{
    /**
     * @throws CreativeNotFoundException
     */
    public function execute(GetCreativeByIdInput $input): GetCreativeByIdOutput;
}
