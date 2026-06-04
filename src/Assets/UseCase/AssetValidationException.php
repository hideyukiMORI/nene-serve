<?php

declare(strict_types=1);

namespace NeneServe\Assets\UseCase;

use RuntimeException;

/** Unsupported content type or oversized upload. */
final class AssetValidationException extends RuntimeException
{
}
