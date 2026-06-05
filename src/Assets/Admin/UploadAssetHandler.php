<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/assets (operationId `uploadAsset`). Requires `manage_creatives`.
 * Accepts a base64-encoded image/video in JSON (multipart streaming is a
 * follow-up for large video). Returns the asset + its public serve URL.
 */
final readonly class UploadAssetHandler
{
    public function __construct(
        private UploadAssetUseCaseInterface $upload,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        $body = JsonRequestBodyParser::parse($request);

        $contentType = isset($body['content_type']) && is_string($body['content_type']) ? $body['content_type'] : null;
        $dataBase64 = isset($body['data_base64']) && is_string($body['data_base64']) ? $body['data_base64'] : null;

        if ($contentType === null || $dataBase64 === null) {
            throw new ValidationException([new ValidationError('data_base64', 'content_type and data_base64 are required.', 'required')]);
        }

        $bytes = base64_decode($dataBase64, true);

        if ($bytes === false) {
            throw new ValidationException([new ValidationError('data_base64', 'data_base64 is not valid base64.', 'invalid')]);
        }

        $asset = $this->upload->execute(new UploadAssetInput($context->userId, $contentType, $bytes))->asset;

        return $this->response->create($asset->toArray(), 201);
    }
}
