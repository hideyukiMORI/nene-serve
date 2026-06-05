<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Assets\PdoAssetRepository;
use NeneServe\Storage\StorageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * GET /public/assets/{id} (operationId `getAsset`). Streams uploaded media by
 * opaque id with a fixed Content-Type and `X-Content-Type-Options: nosniff`;
 * the file is never executed (served from storage, not the web root).
 */
final readonly class AssetHandler
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private StorageInterface $storage,
        private ProblemDetailsResponseFactory $problemDetails,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Router::param($request, 'id') ?? '';

        $asset = (new PdoAssetRepository($this->query))->findById($id);
        $bytes = $asset !== null ? $this->storage->get($asset->id) : null;

        if ($asset === null || $bytes === null) {
            return $this->problemDetails->create($request, 'asset-not-found', 'Asset not found', 404, 'No asset matches the id.');
        }

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', $asset->contentType)
            ->withHeader('Content-Length', (string) strlen($bytes))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Content-Disposition', 'inline')
            ->withHeader('Cache-Control', 'public, max-age=86400')
            ->withBody($this->streamFactory->createStream($bytes));
    }
}
