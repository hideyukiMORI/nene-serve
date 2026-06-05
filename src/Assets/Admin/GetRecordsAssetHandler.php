<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Tenant\Auth\AuthContextResolver;
use NeneServe\Upstream\Records\RecordsClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/records-assets/{ref} (operationId `getRecordsAsset`). Requires
 * `manage_creatives`. Read-only proxy of NeNe Records asset metadata for
 * prefilling creative creation (ADR 0002, read-only). Unknown ref → 404; a
 * transport error → 502 (via {@see RecordsUnavailableExceptionHandler}).
 */
final readonly class GetRecordsAssetHandler
{
    public function __construct(
        private RecordsClientInterface $records,
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

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $ref = is_array($parameters) && is_string($parameters['ref'] ?? null) ? $parameters['ref'] : '';

        $asset = $this->records->fetchAsset($ref);

        if ($asset === null) {
            return $this->problemDetails->create($request, 'records-asset-not-found', 'Records asset not found', 404, 'No Records asset matches the reference.');
        }

        return $this->response->create($asset->toArray());
    }
}
