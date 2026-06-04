<?php

declare(strict_types=1);

namespace NeneServe\Tenant\UseCase;

use RuntimeException;

/**
 * Login failed. Deliberately undifferentiated (unknown org, unknown email,
 * wrong password, disabled account all look identical) to avoid account
 * enumeration. Maps to Problem Details `unauthorized` (401).
 */
final class AuthenticationFailedException extends RuntimeException
{
}
