<?php

declare(strict_types=1);

namespace NeneServe\Http;

/**
 * Minimal value object for an HTTP response.
 *
 * NENE2 parity: in the full runtime this is produced by the framework
 * response factory; here it is a dependency-free stand-in so the scaffold
 * boots before the NENE2 packages are wired in.
 */
final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
    ) {
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
