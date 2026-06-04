<?php

declare(strict_types=1);

namespace NeneServe\Http\PublicApi;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\DestinationUrl;
use NeneServe\Serving\Token\TokenStoreInterface;

/**
 * GET /public/clicks/{click_token} (operationId `redirectClick`). Single-use,
 * short-TTL token → 302 to the creative's **registered** destination. No open
 * redirect: the target comes only from the token, and is re-validated as a safe
 * URL at redirect time (ADR 0019 §2–3). Expired/used/unknown → 404, never a
 * fallback redirect.
 */
final class RedirectClickHandler
{
    public function __construct(
        private readonly TokenStoreInterface $tokens,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        if (!$this->rateLimiter->allow('click:' . $request->clientIp)) {
            return $this->json->problem(429, 'too-many-requests', 'Rate limit exceeded');
        }

        $token = (string) $request->param('click_token');
        $redirect = $this->tokens->consumeClickToken($token);
        if ($redirect === null) {
            return $this->json->problem(404, 'click-token-invalid', 'Click token invalid or expired');
        }

        if (!DestinationUrl::isSafe($redirect->destinationUrl)) {
            // Defense in depth: a stored unsafe destination must never redirect.
            return $this->json->problem(422, 'destination-url-not-registered', 'Destination is not a safe redirect target');
        }

        return new Response(302, '', [
            'Location' => $redirect->destinationUrl,
            'Cache-Control' => 'no-store',
        ]);
    }
}
