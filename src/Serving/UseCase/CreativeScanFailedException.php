<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/**
 * An HTML5 bundle whose malware scan is not `clean` cannot be submitted or
 * served (ADR 0021 §4). Maps to Problem Details `creative-scan-failed` (422).
 */
final class CreativeScanFailedException extends RuntimeException
{
}
