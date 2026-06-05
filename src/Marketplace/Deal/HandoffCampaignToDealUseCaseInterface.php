<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use NeneServe\Marketplace\UseCase\CampaignNotFoundException;
use NeneServe\Marketplace\UseCase\DealHandoffFailedException;

interface HandoffCampaignToDealUseCaseInterface
{
    /**
     * @throws CampaignNotFoundException
     * @throws DealHandoffFailedException
     */
    public function execute(HandoffCampaignToDealInput $input): HandoffCampaignToDealOutput;
}
