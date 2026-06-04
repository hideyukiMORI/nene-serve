<?php

declare(strict_types=1);

namespace NeneServe\Http;

/**
 * Produces JSON responses and RFC 9457 Problem Details errors.
 *
 * NENE2 reuse target (docs/development/nene2-compliance.md): the real
 * runtime swaps this for the framework's JsonResponseFactory. Error bodies
 * follow application/problem+json per ADR 0018 (Problem Details slugs).
 */
final class JsonResponseFactory
{
    private const PROBLEM_BASE = 'https://serve.nene.example/problems/';

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function ok(array $data, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ['Content-Type' => 'application/json; charset=utf-8'] + $headers,
        );
    }

    /**
     * RFC 9457 Problem Details. `type` is derived from a stable slug so the
     * registered Problem Details slugs (ADR 0018) stay machine-stable.
     */
    public function problem(int $status, string $slug, string $title, ?string $detail = null): Response
    {
        $body = [
            'type' => self::PROBLEM_BASE . $slug,
            'title' => $title,
            'status' => $status,
        ];
        if ($detail !== null) {
            $body['detail'] = $detail;
        }

        return new Response(
            $status,
            (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ['Content-Type' => 'application/problem+json; charset=utf-8'],
        );
    }
}
