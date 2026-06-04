<?php

declare(strict_types=1);

namespace NeneServe\Tenant\UseCase;

use RuntimeException;

/** Invalid user input (bad email/role) or duplicate email. */
final class UserValidationException extends RuntimeException
{
}
