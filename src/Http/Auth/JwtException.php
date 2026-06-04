<?php

declare(strict_types=1);

namespace NeneServe\Http\Auth;

use RuntimeException;

/** Raised when a JWT is malformed, has a bad signature, or is expired. */
final class JwtException extends RuntimeException
{
}
