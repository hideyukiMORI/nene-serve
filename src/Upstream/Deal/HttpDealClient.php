<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Deal;

/**
 * Production Deal client: POSTs a net opportunity to the Deal `/api/*` surface
 * with a scoped service token + an `Idempotency-Key` of the external_reference
 * (ADR 0002). No tax figure is sent. Non-2xx/transport → {@see DealClientException}.
 */
final class HttpDealClient implements DealClientInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $serviceToken,
        private readonly int $timeoutSeconds = 10,
    ) {
    }

    public function createOpportunity(
        string $externalReference,
        string $advertiserName,
        string $campaignName,
        int $amountCents,
    ): DealOpportunityResult {
        $payload = (string) json_encode([
            'external_reference' => $externalReference,
            'advertiser_name' => $advertiserName,
            'campaign_name' => $campaignName,
            'amount_cents' => $amountCents, // net, JPY — no tax
            'currency' => 'JPY',
        ]);

        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => implode("\r\n", [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->serviceToken,
                'Idempotency-Key: ' . $externalReference,
            ]),
            'content' => $payload,
            'timeout' => $this->timeoutSeconds,
            'ignore_errors' => true,
        ]]);

        $body = @file_get_contents(rtrim($this->baseUrl, '/') . '/api/opportunities', false, $context);
        if ($body === false) {
            throw new DealClientException('Deal handoff transport error.');
        }

        /** @var list<string> $http_response_header populated by the http wrapper */
        $status = self::statusCode($http_response_header);
        if ($status < 200 || $status >= 300) {
            throw new DealClientException('Deal handoff returned HTTP ' . $status . '.');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !is_string($decoded['opportunity_id'] ?? null)) {
            throw new DealClientException('Deal handoff returned an unexpected body.');
        }

        return new DealOpportunityResult(
            (string) $decoded['opportunity_id'],
            is_string($decoded['status'] ?? null) ? $decoded['status'] : 'created',
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
