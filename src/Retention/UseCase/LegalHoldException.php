<?php

declare(strict_types=1);

namespace NeneServe\Retention\UseCase;

use RuntimeException;

/** Invalid legal-hold operation. Maps to `validation-failed` (422). */
final class LegalHoldException extends RuntimeException
{
}
