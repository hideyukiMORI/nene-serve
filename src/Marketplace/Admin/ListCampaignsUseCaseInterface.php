<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

interface ListCampaignsUseCaseInterface
{
    public function execute(ListCampaignsInput $input): ListCampaignsOutput;
}
