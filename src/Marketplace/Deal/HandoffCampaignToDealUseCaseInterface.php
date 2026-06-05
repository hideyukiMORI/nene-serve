<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use NeneServe\Marketplace\DealOpportunity;
use NeneServe\Marketplace\UseCase\CampaignNotFoundException;
use NeneServe\Marketplace\UseCase\DealHandoffFailedException;
use NeneServe\Tenant\AuthContext;

interface HandoffCampaignToDealUseCaseInterface
{
    /**
     * @throws CampaignNotFoundException
     * @throws DealHandoffFailedException
     */
    public function execute(AuthContext $actor, string $campaignId): DealOpportunity;
}
