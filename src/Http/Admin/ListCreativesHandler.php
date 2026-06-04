<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/** GET /admin/creatives (operationId `listCreatives`). Tenant-scoped. */
final class ListCreativesHandler
{
    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $creatives = array_map(
            static fn ($c) => $c->toAdminArray(),
            $this->creatives->listByOrganization($context->organizationId),
        );

        return $this->json->ok(['creatives' => $creatives]);
    }
}
