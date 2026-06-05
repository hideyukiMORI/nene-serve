<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use RuntimeException;

/** The SMTP test email could not be sent (transport error). Maps to `smtp-test-failed` (502). */
final class SmtpTestFailedException extends RuntimeException
{
}
