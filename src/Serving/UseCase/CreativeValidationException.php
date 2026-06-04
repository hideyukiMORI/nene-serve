<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/**
 * Creative input failed acceptance/validation (ADR 0021 §3) or a required
 * review reason was missing. Maps to `validation-failed` (422).
 */
final class CreativeValidationException extends RuntimeException
{
}
