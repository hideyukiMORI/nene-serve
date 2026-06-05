<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use RuntimeException;

/** A test was requested before SMTP was configured. Maps to `smtp-not-configured` (422). */
final class SmtpNotConfiguredException extends RuntimeException
{
}
