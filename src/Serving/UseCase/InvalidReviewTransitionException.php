<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use RuntimeException;

/** Disallowed review-status change. Maps to `invalid-review-transition` (409). */
final class InvalidReviewTransitionException extends RuntimeException
{
}
