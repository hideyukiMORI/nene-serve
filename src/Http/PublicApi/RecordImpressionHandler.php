<?php

declare(strict_types=1);

namespace NeneServe\Http\PublicApi;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\RecordImpressionUseCase;

/**
 * POST /public/events/impression (operationId `recordImpression`). Idempotent
 * beacon (ADR 0019 §4). The hashed visitor bucket is recorded only when the body
 * carries a positive `consent_state` (privacy §3); unknown tokens ack silently
 * (204) to avoid enumeration.
 */
final class RecordImpressionHandler
{
    public function __construct(
        private readonly RecordImpressionUseCase $recordImpression,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        if (!$this->rateLimiter->allow('impression:' . $request->clientIp)) {
            return $this->json->problem(429, 'too-many-requests', 'Rate limit exceeded');
        }

        $body = $request->json();
        $token = $body['impression_token'] ?? $request->query['impression_token'] ?? null;
        if (!is_string($token) || $token === '') {
            return $this->json->problem(422, 'validation-failed', 'impression_token is required');
        }

        $consentGranted = ($body['consent_state'] ?? null) === 'granted';
        $countryCode = is_string($body['country_code'] ?? null) ? $body['country_code'] : null;
        $pageUrl = is_string($body['page_url'] ?? null) ? $body['page_url'] : null;

        $this->recordImpression->execute(
            $token,
            $request->clientIp,
            $request->header('user-agent') ?? '',
            $consentGranted,
            $countryCode,
            $pageUrl,
        );

        return new Response(204, '');
    }
}
