<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Deal;

use RuntimeException;

/**
 * The Deal handoff transport failed. Isolated by the caller: serving is never
 * affected (Deal is optional), and the handoff is retryable (idempotent on
 * external_reference).
 */
final class DealClientException extends RuntimeException
{
}
