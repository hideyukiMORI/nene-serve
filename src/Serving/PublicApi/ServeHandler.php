<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\UseCase\FrequencyCappedException;
use NeneServe\Serving\UseCase\NoEligibleCreativeException;
use NeneServe\Serving\UseCase\OriginNotAllowedException;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /public/placements/{public_placement_key}/serve (operationId
 * `serveCreative`). Untrusted surface: origin-gated, throttled, exposes only
 * opaque tokens and the render payload (api-security §2). CORS reflects the
 * placement allowlist — never `*`.
 */
final readonly class ServeHandler
{
    public function __construct(
        private ServeCreativeUseCaseInterface $serve,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $key = Router::param($request, 'public_placement_key') ?? '';

        $origin = $request->getHeaderLine('Origin');
        $origin = $origin === '' ? null : $origin;
        $consentGranted = ($request->getQueryParams()['consent'] ?? null) === 'granted';
        $clientIp = PublicRequest::clientIp($request);

        try {
            $result = $this->serve->execute($key, $origin, $consentGranted, $clientIp, $request->getHeaderLine('User-Agent'));
        } catch (PlacementNotFoundException) {
            return $this->problemDetails->create($request, 'placement-not-found', 'Placement not found', 404, 'No placement matches the key.');
        } catch (OriginNotAllowedException) {
            return $this->problemDetails->create($request, 'origin-not-allowed', 'Origin not allowed', 403, 'The requesting origin is not allowlisted for this placement.');
        } catch (NoEligibleCreativeException | FrequencyCappedException) {
            // Empty serve — not an error, nothing counted (measurement-spec).
            return $this->withCors($this->responseFactory->createResponse(204), null);
        }

        $response = $this->response->create($result->payload, 200);

        return $this->withCors($response, $result->corsOrigin);
    }

    private function withCors(ResponseInterface $response, ?string $corsOrigin): ResponseInterface
    {
        $response = $response
            ->withHeader('Vary', 'Origin')
            ->withHeader('Cache-Control', 'no-store');

        if ($corsOrigin !== null) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $corsOrigin);
        }

        return $response;
    }
}
