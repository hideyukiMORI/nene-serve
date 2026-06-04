<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use RuntimeException;

/** Invalid marketplace input. Maps to Problem Details `validation-failed` (422). */
final class MarketplaceValidationException extends RuntimeException
{
}
