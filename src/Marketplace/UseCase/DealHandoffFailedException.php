<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use RuntimeException;

/**
 * The Deal handoff transport failed. Serving is unaffected (Deal is optional);
 * the opportunity is recorded `failed` and is retryable. Maps to
 * `deal-handoff-failed` (502).
 */
final class DealHandoffFailedException extends RuntimeException
{
}
