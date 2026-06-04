<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/** GET /admin/campaigns (operationId `listCampaigns`). Requires `manage_marketplace`; tenant-scoped. */
final class ListCampaignsHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $campaigns = array_map(
            static fn ($c) => $c->toArray(),
            $this->campaigns->listByOrganization($context->organizationId),
        );

        return $this->json->ok(['campaigns' => $campaigns]);
    }
}
