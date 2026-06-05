<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

interface ListCreativesUseCaseInterface
{
    public function execute(ListCreativesInput $input): ListCreativesOutput;
}
