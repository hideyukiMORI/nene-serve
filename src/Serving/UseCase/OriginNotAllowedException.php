<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/** Request Origin not in the placement allowlist. Maps to `origin-not-allowed` (403). */
final class OriginNotAllowedException extends RuntimeException
{
}
