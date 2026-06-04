<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/** GET /admin/creatives/{id} (operationId `getCreativeById`). Requires `manage_creatives`; tenant-scoped. */
final class GetCreativeHandler
{
    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $creative = $this->creatives->findByIdInOrganization(
            (string) $request->param('id'),
            $context->organizationId,
        );
        if ($creative === null) {
            return $this->json->problem(404, 'creative-not-found', 'Creative not found');
        }

        return $this->json->ok($creative->toAdminArray());
    }
}
