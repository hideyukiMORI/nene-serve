<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/**
 * The consent-gated visitor has reached the placement frequency cap. The serve
 * is suppressed (empty 204, non-billable) — not an error. Without consent no cap
 * is applied (fail open to serve, not to track — privacy ADR 0017 §3).
 */
final class FrequencyCappedException extends RuntimeException
{
}
