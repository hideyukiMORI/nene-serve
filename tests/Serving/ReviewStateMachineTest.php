<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use NeneServe\Serving\ReviewStatus;
use PHPUnit\Framework\TestCase;

final class ReviewStateMachineTest extends TestCase
{
    public function testLegalTransitions(): void
    {
        self::assertTrue(ReviewStatus::Draft->canTransitionTo(ReviewStatus::Submitted));
        self::assertTrue(ReviewStatus::ChangesRequested->canTransitionTo(ReviewStatus::Submitted));
        self::assertTrue(ReviewStatus::Submitted->canTransitionTo(ReviewStatus::InReview));
        self::assertTrue(ReviewStatus::InReview->canTransitionTo(ReviewStatus::Approved));
        self::assertTrue(ReviewStatus::InReview->canTransitionTo(ReviewStatus::Rejected));
        self::assertTrue(ReviewStatus::InReview->canTransitionTo(ReviewStatus::ChangesRequested));
    }

    public function testIllegalTransitions(): void
    {
        self::assertFalse(ReviewStatus::Draft->canTransitionTo(ReviewStatus::Approved));
        self::assertFalse(ReviewStatus::Submitted->canTransitionTo(ReviewStatus::Approved));
        self::assertFalse(ReviewStatus::Approved->canTransitionTo(ReviewStatus::Draft));
        self::assertFalse(ReviewStatus::Rejected->canTransitionTo(ReviewStatus::Approved));
    }

    public function testOnlyApprovedIsServable(): void
    {
        self::assertTrue(ReviewStatus::Approved->isServable());
        foreach ([ReviewStatus::Draft, ReviewStatus::Submitted, ReviewStatus::InReview, ReviewStatus::Rejected, ReviewStatus::ChangesRequested] as $status) {
            self::assertFalse($status->isServable(), $status->value . ' must not serve');
        }
    }

    public function testEditableStates(): void
    {
        self::assertTrue(ReviewStatus::Draft->isEditable());
        self::assertTrue(ReviewStatus::ChangesRequested->isEditable());
        self::assertFalse(ReviewStatus::Submitted->isEditable());
        self::assertFalse(ReviewStatus::Approved->isEditable());
    }
}
