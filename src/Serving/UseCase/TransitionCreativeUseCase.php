<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\Review\ReviewAction;
use NeneServe\Tenant\AuthContext;

/**
 * Drives the review state machine (creative-review §1). Enforces legal
 * transitions, four-eyes approval (ADR 0020 §4), required reasons, and writes an
 * audit record for every decision (§6). Capability gating is at the route guard.
 */
final class TransitionCreativeUseCase
{
    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly AuditLogInterface $audit,
    ) {
    }

    public function execute(
        AuthContext $actor,
        string $creativeId,
        ReviewAction $action,
        ?string $reason = null,
        bool $selfApprovalOverride = false,
    ): Creative {
        $creative = $this->creatives->findByIdInOrganization($creativeId, $actor->organizationId);
        if ($creative === null) {
            throw new CreativeNotFoundException();
        }

        $target = $action->target();
        if (!$creative->reviewStatus->canTransitionTo($target)) {
            throw new InvalidReviewTransitionException(sprintf(
                'Cannot %s a creative in %s.',
                $action->value,
                $creative->reviewStatus->value,
            ));
        }

        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
        if ($action->requiresReason() && $reason === null) {
            throw new CreativeValidationException(sprintf('%s requires a review_reason.', $action->value));
        }

        if ($action === ReviewAction::Approve
            && $creative->submittedBy === $actor->userId
            && !$selfApprovalOverride) {
            throw new SelfApprovalForbiddenException();
        }

        $submittedBy = $action === ReviewAction::Submit ? $actor->userId : null;
        $updated = $creative->withReview($target, $submittedBy, $reason);
        $this->creatives->save($updated);

        $this->audit->record(
            $actor->organizationId,
            $actor->userId,
            'creative.' . $action->value,
            'creative',
            $creative->id,
            array_filter([
                'from' => $creative->reviewStatus->value,
                'to' => $target->value,
                'reason' => $reason,
                'self_approval_override' => $action === ReviewAction::Approve && $selfApprovalOverride ? true : null,
            ], static fn ($v) => $v !== null),
        );

        return $updated;
    }
}
