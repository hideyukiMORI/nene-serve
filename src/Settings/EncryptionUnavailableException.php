<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use RuntimeException;

/** At-rest encryption (Support\Crypto) is not configured. Maps to `encryption-unavailable` (500). */
final class EncryptionUnavailableException extends RuntimeException
{
}
