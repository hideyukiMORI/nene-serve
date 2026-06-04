<?php

declare(strict_types=1);

namespace NeneServe\Serving\Review;

use NeneServe\Serving\ReviewStatus;

/**
 * A review transition the admin surface can request. Maps to the target
 * {@see ReviewStatus} of the state machine (creative-review §1).
 */
enum ReviewAction: string
{
    case Submit = 'submit';
    case StartReview = 'start_review';
    case Approve = 'approve';
    case Reject = 'reject';
    case RequestChanges = 'request_changes';

    public function target(): ReviewStatus
    {
        return match ($this) {
            self::Submit => ReviewStatus::Submitted,
            self::StartReview => ReviewStatus::InReview,
            self::Approve => ReviewStatus::Approved,
            self::Reject => ReviewStatus::Rejected,
            self::RequestChanges => ReviewStatus::ChangesRequested,
        };
    }

    public function requiresReason(): bool
    {
        return $this === self::Reject || $this === self::RequestChanges;
    }
}
