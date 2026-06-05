<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\Csp;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\Token\TokenStoreInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * GET /public/frames/{frame_token} (operationId `getCreativeFrame`). Serves an
 * approved HTML5 bundle inside a **strict-CSP sandbox** (ADR 0021 §4): the
 * response carries a `Content-Security-Policy` with a `sandbox` directive (no
 * same-origin, no top navigation), `script-src 'self'` (no `eval`), and
 * `default-src 'none'`. Only an approved, scan-clean html5 creative renders;
 * anything else → 404. The token is opaque so no internal id is exposed.
 */
final readonly class CreativeFrameHandler
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private TokenStoreInterface $tokens,
        private ProblemDetailsResponseFactory $problemDetails,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $token = is_array($parameters) && is_string($parameters['frame_token'] ?? null) ? $parameters['frame_token'] : '';

        $target = $this->tokens->resolveFrameToken($token);

        if ($target === null) {
            return $this->problemDetails->create($request, 'creative-not-found', 'Frame not found', 404, 'The frame token is invalid or expired.');
        }

        $creative = (new PdoCreativeRepository($this->query))->findByIdInOrganization($target->creativeId, $target->organizationId);

        if ($creative === null
            || $creative->type !== CreativeType::Html5Bundle
            || !$creative->isServable()
            || !$creative->isScanClean()) {
            return $this->problemDetails->create($request, 'creative-not-found', 'Frame not available', 404, 'No servable html5 creative for this frame.');
        }

        // Placeholder document — real asset hosting serves the bundle's index.html.
        // The security guarantee is the CSP/sandbox response headers below.
        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<title>creative</title></head><body><!-- bundle '
            . htmlspecialchars((string) $creative->bundleId, ENT_QUOTES) . ' --></body></html>';

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Content-Security-Policy', Csp::html5Frame())
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'no-store')
            ->withBody($this->streamFactory->createStream($html));
    }
}
