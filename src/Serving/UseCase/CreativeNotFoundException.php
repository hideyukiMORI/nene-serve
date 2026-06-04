<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/** Creative id not found in the tenant. Maps to `creative-not-found` (404). */
final class CreativeNotFoundException extends RuntimeException
{
}
