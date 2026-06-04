<?php

declare(strict_types=1);

namespace NeneServe\Http\PublicApi;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\Csp;
use NeneServe\Serving\Token\TokenStoreInterface;

/**
 * GET /public/frames/{frame_token} (operationId `getCreativeFrame`). Serves an
 * approved HTML5 bundle inside a **strict-CSP sandbox** (ADR 0021 §4): the
 * response carries a `Content-Security-Policy` with a `sandbox` directive (no
 * same-origin, no top navigation), `script-src 'self'` (no `eval`), and
 * `default-src 'none'`. Only an approved, scan-clean html5 creative renders;
 * anything else → 404. The token is opaque so no internal id is exposed.
 */
final class CreativeFrameHandler
{
    public function __construct(
        private readonly TokenStoreInterface $tokens,
        private readonly CreativeRepositoryInterface $creatives,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        if (!$this->rateLimiter->allow('frame:' . $request->clientIp)) {
            return $this->json->problem(429, 'too-many-requests', 'Rate limit exceeded');
        }

        $target = $this->tokens->resolveFrameToken((string) $request->param('frame_token'));
        if ($target === null) {
            return $this->json->problem(404, 'creative-not-found', 'Frame not found');
        }

        $creative = $this->creatives->findByIdInOrganization($target->creativeId, $target->organizationId);
        if ($creative === null
            || $creative->type !== CreativeType::Html5Bundle
            || !$creative->isServable()
            || !$creative->isScanClean()) {
            return $this->json->problem(404, 'creative-not-found', 'Frame not available');
        }

        // Placeholder document — real asset hosting serves the bundle's index.html.
        // The security guarantee is the CSP/sandbox response headers below.
        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<title>creative</title></head><body><!-- bundle '
            . htmlspecialchars((string) $creative->bundleId, ENT_QUOTES) . ' --></body></html>';

        return new Response(200, $html, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Security-Policy' => Csp::html5Frame(),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }
}
