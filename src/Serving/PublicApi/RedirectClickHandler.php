<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\DestinationUrl;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /public/clicks/{click_token} (operationId `redirectClick`). Records the
 * click (measurement-spec) then 302s to the creative's **registered**
 * destination. Single-use, short-TTL token; expired/used/unknown → 404, never a
 * fallback redirect. No open redirect — destination re-validated at redirect
 * time (ADR 0019 §2–3).
 */
final readonly class RedirectClickHandler
{
    public function __construct(
        private RecordClickUseCaseInterface $recordClick,
        private ProblemDetailsResponseFactory $problemDetails,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = Router::param($request, 'click_token') ?? '';

        $countryCode = is_string($request->getQueryParams()['country_code'] ?? null) ? $request->getQueryParams()['country_code'] : null;

        $redirect = $this->recordClick->execute($token, $countryCode);

        if ($redirect === null) {
            return $this->problemDetails->create($request, 'click-token-invalid', 'Click token invalid or expired', 404, 'The click token is invalid, used, or expired.');
        }

        if (!DestinationUrl::isSafe($redirect->destinationUrl)) {
            return $this->problemDetails->create($request, 'destination-url-not-registered', 'Destination is not a safe redirect target', 422, 'The destination URL is not a safe redirect target.');
        }

        return $this->responseFactory->createResponse(302)
            ->withHeader('Location', $redirect->destinationUrl)
            ->withHeader('Cache-Control', 'no-store');
    }
}
