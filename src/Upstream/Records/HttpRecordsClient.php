<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Records;

/**
 * Production Records client: a **read-only** GET to the Records `/api/*` surface
 * with a scoped service token (sibling-products map, ADR 0002). 404 → null;
 * other non-2xx or transport errors → {@see RecordsClientException}.
 */
final class HttpRecordsClient implements RecordsClientInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $serviceToken,
        private readonly int $timeoutSeconds = 10,
    ) {
    }

    public function fetchAsset(string $ref): ?RecordsAsset
    {
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $this->serviceToken,
            'timeout' => $this->timeoutSeconds,
            'ignore_errors' => true,
        ]]);

        $url = rtrim($this->baseUrl, '/') . '/api/assets/' . rawurlencode($ref);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new RecordsClientException('Records read transport error.');
        }

        /** @var list<string> $http_response_header populated by the http wrapper */
        $status = self::statusCode($http_response_header);
        if ($status === 404) {
            return null;
        }
        if ($status < 200 || $status >= 300) {
            throw new RecordsClientException('Records read returned HTTP ' . $status . '.');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !is_string($decoded['image_url'] ?? null)) {
            throw new RecordsClientException('Records read returned an unexpected body.');
        }

        return new RecordsAsset(
            $ref,
            (string) $decoded['image_url'],
            is_int($decoded['width'] ?? null) ? $decoded['width'] : null,
            is_int($decoded['height'] ?? null) ? $decoded['height'] : null,
        );
    }

    /** @param list<string> $headers */
    private static function statusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m) === 1) {
                return (int) $m[1];
            }
        }

        return 0;
    }
}
