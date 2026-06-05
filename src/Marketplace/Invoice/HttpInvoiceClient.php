<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Invoice;

use NeneServe\Support\HttpStatusLine;

/**
 * Production Invoice client (handoff contract, ADR 0002): POSTs a **net** charge
 * to the Invoice `/api/*` surface with a scoped service token and an
 * `Idempotency-Key` of the external_reference. The payload carries no tax figure
 * or rate. Any non-2xx or transport error raises {@see InvoiceClientException}
 * so the caller can isolate the failure and retry safely.
 */
final class HttpInvoiceClient implements InvoiceClientInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $serviceToken,
        private readonly int $timeoutSeconds = 10,
    ) {
    }

    public function postCharge(
        string $externalReference,
        ?string $invoiceClientId,
        int $amountCents,
        string $periodStart,
        string $periodEnd,
    ): InvoiceChargeResult {
        $payload = (string) json_encode(array_filter([
            'external_reference' => $externalReference,
            'invoice_client_id' => $invoiceClientId,
            'amount_cents' => $amountCents,   // net, JPY minimum units — no tax
            'currency' => 'JPY',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ], static fn ($v) => $v !== null));

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

        $body = @file_get_contents(rtrim($this->baseUrl, '/') . '/api/charges', false, $context);
        if ($body === false) {
            throw new InvoiceClientException('Invoice handoff transport error.');
        }

        /** @var list<string> $http_response_header populated by the http wrapper */
        $status = HttpStatusLine::statusCode($http_response_header);
        if ($status < 200 || $status >= 300) {
            throw new InvoiceClientException('Invoice handoff returned HTTP ' . $status . '.');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !is_string($decoded['invoice_payment_id'] ?? null)) {
            throw new InvoiceClientException('Invoice handoff returned an unexpected body.');
        }

        return new InvoiceChargeResult(
            (string) $decoded['invoice_payment_id'],
            is_string($decoded['status'] ?? null) ? $decoded['status'] : 'recorded',
        );
    }

    /** @param list<string> $headers */
}
