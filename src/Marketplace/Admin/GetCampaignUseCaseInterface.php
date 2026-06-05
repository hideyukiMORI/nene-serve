<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\UseCase\CampaignNotFoundException;

interface GetCampaignUseCaseInterface
{
    /**
     * @throws CampaignNotFoundException
     */
    public function execute(GetCampaignInput $input): GetCampaignOutput;
}
