<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /public/events/conversion (operationId `recordConversion`). The Concierge
 * conversion beacon (ADR 0009): logs an append-only conversion against a
 * placement — never a Contact submission. Throttled; no PII; opt-out aware.
 */
final readonly class RecordConversionHandler
{
    public function __construct(
        private RecordConversionUseCaseInterface $recordConversion,
        private ProblemDetailsResponseFactory $problemDetails,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = PublicRequest::jsonBody($request);

        $key = $body['public_placement_key'] ?? null;

        if (!is_string($key) || $key === '') {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation failed', 422, 'public_placement_key is required.');
        }

        $creativeId = is_string($body['creative_id'] ?? null) ? $body['creative_id'] : null;
        $countryCode = is_string($body['country_code'] ?? null) ? $body['country_code'] : null;

        try {
            $this->recordConversion->execute($key, $creativeId, $countryCode);
        } catch (PlacementNotFoundException) {
            return $this->problemDetails->create($request, 'placement-not-found', 'Placement not found', 404, 'No placement matches the key.');
        }

        return $this->responseFactory->createResponse(204);
    }
}
