<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

final class ServeResult
{
    /**
     * @param array<string, mixed> $payload public render payload (no internal ids)
     * @param string|null $corsOrigin origin to reflect in CORS, or null for none (never `*`)
     */
    public function __construct(
        public readonly array $payload,
        public readonly ?string $corsOrigin,
    ) {
    }
}
