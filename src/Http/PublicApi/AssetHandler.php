<?php

declare(strict_types=1);

namespace NeneServe\Http\PublicApi;

use NeneServe\Assets\AssetRepositoryInterface;
use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Storage\StorageInterface;

/**
 * GET /public/assets/{id} (operationId `getAsset`). Streams uploaded media by
 * opaque id with a fixed Content-Type and `X-Content-Type-Options: nosniff`;
 * the file is never executed (served from storage, not the web root).
 */
final class AssetHandler
{
    public function __construct(
        private readonly AssetRepositoryInterface $assets,
        private readonly StorageInterface $storage,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        $id = (string) $request->param('id');
        if (!$this->rateLimiter->allow('asset:' . $request->clientIp)) {
            return $this->json->problem(429, 'too-many-requests', 'Rate limit exceeded');
        }

        $asset = $this->assets->findById($id);
        $bytes = $asset !== null ? $this->storage->get($asset->id) : null;
        if ($asset === null || $bytes === null) {
            return $this->json->problem(404, 'asset-not-found', 'Asset not found');
        }

        return new Response(200, $bytes, [
            'Content-Type' => $asset->contentType,
            'Content-Length' => (string) strlen($bytes),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
