<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/**
 * The submitter tried to approve their own creative without an audited override
 * (four-eyes, ADR 0020 §4). Maps to `self-approval-forbidden` (403).
 */
final class SelfApprovalForbiddenException extends RuntimeException
{
}
