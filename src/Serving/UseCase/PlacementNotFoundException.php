<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/** Public placement key not found. Maps to Problem Details `placement-not-found` (404). */
final class PlacementNotFoundException extends RuntimeException
{
}
