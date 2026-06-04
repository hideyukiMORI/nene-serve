<?php

declare(strict_types=1);

namespace NeneServe\Http\PublicApi;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\Token\TokenStoreInterface;

/**
 * POST /public/events/impression (operationId `recordImpression`). Idempotent
 * beacon: replaying a token does not inflate counts (ADR 0019 §4). Unknown
 * tokens are accepted silently (204) to avoid token enumeration; event
 * persistence proper lands in #14.
 */
final class RecordImpressionHandler
{
    public function __construct(
        private readonly TokenStoreInterface $tokens,
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

        // Records on first call, no-ops on replay; either way the beacon acks 204.
        $this->tokens->recordImpression($token);

        return new Response(204, '');
    }
}
