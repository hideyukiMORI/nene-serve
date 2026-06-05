<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\Review\ReviewAction;
use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\CreativeScanFailedException;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Serving\UseCase\SelfApprovalForbiddenException;
use NeneServe\Tenant\AuthContext;

/**
 * Drives the review state machine (creative-review §1): legal transitions,
 * four-eyes approval (ADR 0020 §4), required reasons, HTML5 scan gate, and an
 * audit record per decision (§6) — committed atomically via the NENE2
 * transaction pattern.
 */
final readonly class TransitionCreativeUseCase implements TransitionCreativeUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(
        AuthContext $actor,
        string $creativeId,
        ReviewAction $action,
        ?string $reason = null,
        bool $selfApprovalOverride = false,
    ): Creative {
        $creative = (new PdoCreativeRepository($this->query))->findByIdInOrganization($creativeId, $actor->organizationId);

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

        // An HTML5 bundle may only enter review once its malware scan is clean (ADR 0021 §4).
        if ($action === ReviewAction::Submit
            && $creative->type === CreativeType::Html5Bundle
            && !$creative->isScanClean()) {
            throw new CreativeScanFailedException('HTML5 bundle scan must be clean before submission.');
        }

        if ($action === ReviewAction::Approve
            && $creative->submittedBy === $actor->userId
            && !$selfApprovalOverride) {
            throw new SelfApprovalForbiddenException();
        }

        $submittedBy = $action === ReviewAction::Submit ? $actor->userId : null;
        $updated = $creative->withReview($target, $submittedBy, $reason);
        $fromStatus = $creative->reviewStatus->value;

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($updated, $actor, $action, $fromStatus, $target, $reason, $selfApprovalOverride): Creative {
                (new PdoCreativeRepository($tx))->save($updated);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'creative.' . $action->value,
                    'creative',
                    $updated->id,
                    array_filter([
                        'before' => ['review_status' => $fromStatus],
                        'after' => ['review_status' => $target->value],
                        'reason' => $reason,
                        'self_approval_override' => $action === ReviewAction::Approve && $selfApprovalOverride ? true : null,
                    ], static fn ($v) => $v !== null),
                );

                return $updated;
            },
        );
    }
}
