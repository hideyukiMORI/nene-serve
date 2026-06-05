<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /public/events/impression (operationId `recordImpression`). Idempotent
 * beacon (ADR 0019 §4). The hashed visitor bucket is recorded only when the body
 * carries a positive `consent_state` (privacy §3); unknown tokens ack silently
 * (204) to avoid enumeration.
 */
final readonly class RecordImpressionHandler
{
    public function __construct(
        private RecordImpressionUseCaseInterface $recordImpression,
        private ProblemDetailsResponseFactory $problemDetails,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = PublicRequest::jsonBody($request);
        $query = $request->getQueryParams();

        $token = $body['impression_token'] ?? $query['impression_token'] ?? null;

        if (!is_string($token) || $token === '') {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation failed', 422, 'impression_token is required.');
        }

        $consentGranted = ($body['consent_state'] ?? null) === 'granted';
        $countryCode = is_string($body['country_code'] ?? null) ? $body['country_code'] : null;
        $pageUrl = is_string($body['page_url'] ?? null) ? $body['page_url'] : null;

        $this->recordImpression->execute(
            $token,
            PublicRequest::clientIp($request),
            $request->getHeaderLine('User-Agent'),
            $consentGranted,
            $countryCode,
            $pageUrl,
        );

        return $this->responseFactory->createResponse(204);
    }
}
