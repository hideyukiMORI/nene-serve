<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Tenant\AuthContext;
use NeneServe\Upstream\Records\RecordsClientException;
use NeneServe\Upstream\Records\RecordsClientInterface;

/**
 * GET /admin/records-assets/{ref} (operationId `getRecordsAsset`). Requires
 * `manage_creatives`. Read-only proxy of NeNe Records asset metadata for
 * prefilling creative creation (ADR 0002, read-only). Unknown ref → 404; a
 * transport error → 502.
 */
final class GetRecordsAssetHandler
{
    public function __construct(
        private readonly RecordsClientInterface $records,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        try {
            $asset = $this->records->fetchAsset((string) $request->param('ref'));
        } catch (RecordsClientException $e) {
            return $this->json->problem(502, 'records-unavailable', 'Records is unavailable', $e->getMessage());
        }

        if ($asset === null) {
            return $this->json->problem(404, 'records-asset-not-found', 'Records asset not found');
        }

        return $this->json->ok($asset->toArray());
    }
}
