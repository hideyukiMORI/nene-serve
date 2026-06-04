<?php

declare(strict_types=1);

namespace NeneServe\Http;

/**
 * Liveness probe. Operational endpoint (not one of the three business API
 * surfaces); requires no authentication and reveals no tenant data.
 */
final class HealthHandler
{
    public function __construct(
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function show(): Response
    {
        return $this->json->ok([
            'status' => 'ok',
            'service' => 'nene-serve',
            'version' => Kernel::VERSION,
            'time' => gmdate('c'),
        ]);
    }
}
