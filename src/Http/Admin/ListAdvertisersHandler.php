<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/** GET /admin/advertisers (operationId `listAdvertisers`). Tenant-scoped. */
final class ListAdvertisersHandler
{
    public function __construct(
        private readonly AdvertiserRepositoryInterface $advertisers,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        return $this->json->ok([
            'advertisers' => array_map(
                static fn ($a) => $a->toArray(),
                $this->advertisers->listByOrganization($context->organizationId),
            ),
        ]);
    }
}
