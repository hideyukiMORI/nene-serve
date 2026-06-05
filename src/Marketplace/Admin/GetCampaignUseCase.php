<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\UseCase\CampaignNotFoundException;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;

final readonly class GetCampaignUseCase implements GetCampaignUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private CampaignRepositoryInterface $campaigns,
        private GetCampaignSpendUseCase $spend,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(GetCampaignInput $input): GetCampaignOutput
    {
        $campaign = $this->campaigns->findByIdInOrganization($input->id, $this->organizationId->get());

        if ($campaign === null) {
            throw new CampaignNotFoundException();
        }

        return new GetCampaignOutput($campaign, $this->spend->forCampaign($campaign));
    }
}
