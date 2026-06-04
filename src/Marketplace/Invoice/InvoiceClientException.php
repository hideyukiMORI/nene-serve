<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Invoice;

use RuntimeException;

/**
 * The Invoice handoff transport failed. The caller isolates the failure: serving
 * is not paused, and the handoff can be safely retried (idempotent on
 * external_reference) — billing handoff contract §failure isolation.
 */
final class InvoiceClientException extends RuntimeException
{
}
