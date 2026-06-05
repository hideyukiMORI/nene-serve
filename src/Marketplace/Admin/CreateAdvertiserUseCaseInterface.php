<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\UseCase\MarketplaceValidationException;

interface CreateAdvertiserUseCaseInterface
{
    /** @throws MarketplaceValidationException */
    public function execute(CreateAdvertiserInput $input): CreateAdvertiserOutput;
}
