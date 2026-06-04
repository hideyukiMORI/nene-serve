<?php

declare(strict_types=1);

namespace NeneServe\Mail;

use RuntimeException;

/** Mail delivery failure (transport/SMTP error). Never leaks recipient secrets. */
final class MailerException extends RuntimeException
{
}
