<?php

declare(strict_types=1);

namespace NeneServe\Tenant\UseCase;

use RuntimeException;

/** Invitation token unknown, already accepted, or expired. */
final class InvitationInvalidException extends RuntimeException
{
}
