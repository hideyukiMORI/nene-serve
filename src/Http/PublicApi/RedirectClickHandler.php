<?php

declare(strict_types=1);

namespace NeneServe\Http\PublicApi;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\RecordClickUseCase;
use NeneServe\Serving\DestinationUrl;

/**
 * GET /public/clicks/{click_token} (operationId `redirectClick`). Records the
 * click (measurement-spec) then 302s to the creative's **registered**
 * destination. Single-use, short-TTL token; expired/used/unknown → 404, never a
 * fallback redirect. No open redirect — destination re-validated at redirect
 * time (ADR 0019 §2–3).
 */
final class RedirectClickHandler
{
    public function __construct(
        private readonly RecordClickUseCase $recordClick,
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
        $countryCode = is_string($request->query['country_code'] ?? null) ? $request->query['country_code'] : null;

        $redirect = $this->recordClick->execute($token, $countryCode);
        if ($redirect === null) {
            return $this->json->problem(404, 'click-token-invalid', 'Click token invalid or expired');
        }

        if (!DestinationUrl::isSafe($redirect->destinationUrl)) {
            return $this->json->problem(422, 'destination-url-not-registered', 'Destination is not a safe redirect target');
        }

        return new Response(302, '', [
            'Location' => $redirect->destinationUrl,
            'Cache-Control' => 'no-store',
        ]);
    }
}
