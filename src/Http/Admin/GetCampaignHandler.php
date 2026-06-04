<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Tenant\AuthContext;

/**
 * GET /admin/campaigns/{id} (operationId `getCampaign`). Requires
 * `manage_marketplace`. Returns the campaign plus its **derived, reproducible**
 * spend (`spent = f(billable_units, pricing_rule_version)`); aggregate money, no
 * visitor identifiers.
 */
final class GetCampaignHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly GetCampaignSpendUseCase $spend,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $campaign = $this->campaigns->findByIdInOrganization((string) $request->param('id'), $context->organizationId);
        if ($campaign === null) {
            return $this->json->problem(404, 'campaign-not-found', 'Campaign not found');
        }

        return $this->json->ok($campaign->toArray() + ['spend' => $this->spend->forCampaign($campaign)->toArray()]);
    }
}
