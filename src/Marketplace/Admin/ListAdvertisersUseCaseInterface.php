<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

interface ListAdvertisersUseCaseInterface
{
    public function execute(ListAdvertisersInput $input): ListAdvertisersOutput;
}
