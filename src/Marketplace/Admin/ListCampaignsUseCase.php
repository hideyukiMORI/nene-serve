<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Marketplace\CampaignRepositoryInterface;

final readonly class ListCampaignsUseCase implements ListCampaignsUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private CampaignRepositoryInterface $campaigns,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(ListCampaignsInput $input): ListCampaignsOutput
    {
        return new ListCampaignsOutput(
            items: $this->campaigns->listByOrganization($this->organizationId->get(), $input->limit, $input->offset),
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
