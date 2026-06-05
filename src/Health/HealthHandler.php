<?php

declare(strict_types=1);

namespace NeneServe\Health;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Unauthenticated liveness probe (ADR 0018). Returns a small JSON body so load
 * balancers and the frontend can confirm the API is up.
 */
final readonly class HealthHandler
{
    public function __construct(
        private JsonResponseFactory $jsonResponses,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->jsonResponses->create(['status' => 'ok']);
    }
}
