<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Assets\UseCase\AssetValidationException;
use NeneServe\Assets\UseCase\UploadAssetUseCase;
use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/assets (operationId `uploadAsset`). Requires `manage_creatives`.
 * Accepts a base64-encoded image/video in JSON (multipart streaming is a
 * follow-up for large video). Returns the asset + its public serve URL.
 */
final class UploadAssetHandler
{
    public function __construct(
        private readonly UploadAssetUseCase $upload,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $contentType = $body['content_type'] ?? null;
        $dataBase64 = $body['data_base64'] ?? null;
        if (!is_string($contentType) || !is_string($dataBase64)) {
            return $this->json->problem(422, 'validation-failed', 'content_type and data_base64 are required');
        }
        $bytes = base64_decode($dataBase64, true);
        if ($bytes === false) {
            return $this->json->problem(422, 'validation-failed', 'data_base64 is not valid base64');
        }

        try {
            $asset = $this->upload->execute($context, $contentType, $bytes);
        } catch (AssetValidationException $e) {
            return $this->json->problem(422, 'asset-invalid', 'Asset rejected', $e->getMessage());
        }

        return $this->json->ok($asset->toArray(), 201);
    }
}
