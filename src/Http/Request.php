<?php

declare(strict_types=1);

namespace NeneServe\Http;

/**
 * Immutable HTTP request value object. Transport-agnostic so handlers and
 * middleware are unit-testable without a live server.
 */
final class Request
{
    /**
     * @param array<string, string> $headers header name (lower-case) => value
     * @param array<string, string> $query
     * @param array<string, string> $params path parameters resolved by the router
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $headers = [],
        public readonly array $query = [],
        public readonly string $body = '',
        public readonly array $params = [],
        public readonly string $clientIp = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with((string) $key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr((string) $key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/',
            $headers,
            array_map('strval', $_GET),
            (string) file_get_contents('php://input'),
            [],
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function param(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }

    /** @param array<string, string> $params */
    public function withParams(array $params): self
    {
        return new self($this->method, $this->path, $this->headers, $this->query, $this->body, $params, $this->clientIp);
    }

    /**
     * Decoded JSON body, or empty array when absent/invalid.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if ($this->body === '') {
            return [];
        }
        $decoded = json_decode($this->body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
