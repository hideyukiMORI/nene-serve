<?php

declare(strict_types=1);

namespace NeneServe\Http\PublicApi;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\RecordConversionUseCase;
use NeneServe\Serving\UseCase\PlacementNotFoundException;

/**
 * POST /public/events/conversion (operationId `recordConversion`). The Concierge
 * conversion beacon (ADR 0009): logs an append-only conversion against a
 * placement — never a Contact submission. Rate-limited; no PII; opt-out aware.
 */
final class RecordConversionHandler
{
    public function __construct(
        private readonly RecordConversionUseCase $recordConversion,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        if (!$this->rateLimiter->allow('conversion:' . $request->clientIp)) {
            return $this->json->problem(429, 'too-many-requests', 'Rate limit exceeded');
        }

        $body = $request->json();
        $key = $body['public_placement_key'] ?? null;
        if (!is_string($key) || $key === '') {
            return $this->json->problem(422, 'validation-failed', 'public_placement_key is required');
        }
        $creativeId = is_string($body['creative_id'] ?? null) ? $body['creative_id'] : null;
        $countryCode = is_string($body['country_code'] ?? null) ? $body['country_code'] : null;

        try {
            $this->recordConversion->execute($key, $creativeId, $countryCode);
        } catch (PlacementNotFoundException) {
            return $this->json->problem(404, 'placement-not-found', 'Placement not found');
        }

        return new Response(204, '');
    }
}
