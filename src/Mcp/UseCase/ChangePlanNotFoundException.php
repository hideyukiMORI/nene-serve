<?php

declare(strict_types=1);

namespace NeneServe\Mcp\UseCase;

use RuntimeException;

/** Change plan / confirmation token not found. Maps to `change-plan-not-found` (404). */
final class ChangePlanNotFoundException extends RuntimeException
{
}
