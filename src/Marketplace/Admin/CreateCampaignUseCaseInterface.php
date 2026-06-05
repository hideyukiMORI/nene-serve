<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\UseCase\MarketplaceValidationException;

interface CreateCampaignUseCaseInterface
{
    /** @throws MarketplaceValidationException */
    public function execute(CreateCampaignInput $input): CreateCampaignOutput;
}
