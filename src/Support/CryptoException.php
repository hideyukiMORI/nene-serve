<?php

declare(strict_types=1);

namespace NeneServe\Support;

use RuntimeException;

/** At-rest encryption/decryption failure (missing key, malformed input, or tampering). */
final class CryptoException extends RuntimeException
{
}
