<?php

declare(strict_types=1);

namespace NeneServe\Http\PublicApi;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\UseCase\NoEligibleCreativeException;
use NeneServe\Serving\UseCase\OriginNotAllowedException;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use NeneServe\Serving\UseCase\ServeCreativeUseCase;

/**
 * GET /public/placements/{public_placement_key}/serve (operationId
 * `serveCreative`). Untrusted surface: origin-gated, rate-limited, exposes only
 * opaque tokens and the render payload (api-security §2). CORS reflects the
 * placement allowlist — never `*`.
 */
final class ServeHandler
{
    public function __construct(
        private readonly ServeCreativeUseCase $serve,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        $key = (string) $request->param('public_placement_key');
        if (!$this->rateLimiter->allow('serve:' . $request->clientIp . ':' . $key)) {
            return $this->json->problem(429, 'too-many-requests', 'Rate limit exceeded');
        }

        $origin = $request->header('origin');

        try {
            $result = $this->serve->execute($key, $origin);
        } catch (PlacementNotFoundException) {
            return $this->json->problem(404, 'placement-not-found', 'Placement not found');
        } catch (OriginNotAllowedException) {
            return $this->json->problem(403, 'origin-not-allowed', 'Origin not allowed');
        } catch (NoEligibleCreativeException) {
            // Empty serve — not an error, nothing counted (measurement-spec).
            return new Response(204, '', $this->corsHeaders(null));
        }

        return $this->json->ok($result->payload, 200, $this->corsHeaders($result->corsOrigin));
    }

    /** @return array<string, string> */
    private function corsHeaders(?string $corsOrigin): array
    {
        $headers = ['Vary' => 'Origin', 'Cache-Control' => 'no-store'];
        if ($corsOrigin !== null) {
            $headers['Access-Control-Allow-Origin'] = $corsOrigin;
        }

        return $headers;
    }
}
