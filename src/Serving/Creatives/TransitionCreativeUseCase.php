<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
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
use NeneServe\Support\SqlDialect;

/**
 * Drives the review state machine (creative-review §1): legal transitions,
 * four-eyes approval (ADR 0020 §4), required reasons, HTML5 scan gate, and an
 * audit record per decision (§6) — committed atomically via the NENE2
 * transaction pattern.
 */
final readonly class TransitionCreativeUseCase implements TransitionCreativeUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function execute(TransitionCreativeInput $input): TransitionCreativeOutput
    {
        $actorUserId = $input->actorUserId;
        $organizationId = $this->organizationId->get();

        $creative = (new PdoCreativeRepository($this->query))->findByIdInOrganization($input->creativeId, $organizationId);

        if ($creative === null) {
            throw new CreativeNotFoundException();
        }

        $action = $input->action;
        $target = $action->target();

        if (!$creative->reviewStatus->canTransitionTo($target)) {
            throw new InvalidReviewTransitionException(sprintf(
                'Cannot %s a creative in %s.',
                $action->value,
                $creative->reviewStatus->value,
            ));
        }

        $reason = $input->reason !== null && trim($input->reason) !== '' ? trim($input->reason) : null;

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
            && $creative->submittedBy === $actorUserId
            && !$input->selfApprovalOverride) {
            throw new SelfApprovalForbiddenException();
        }

        $submittedBy = $action === ReviewAction::Submit ? $actorUserId : null;
        $updated = $creative->withReview($target, $submittedBy, $reason);
        $fromStatus = $creative->reviewStatus->value;
        $selfApprovalOverride = $input->selfApprovalOverride;

        $dialect = $this->dialect;
        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($updated, $organizationId, $actorUserId, $action, $fromStatus, $target, $reason, $selfApprovalOverride, $dialect): Creative {
                (new PdoCreativeRepository($tx, $dialect))->save($updated);
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $actorUserId,
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

        return new TransitionCreativeOutput($stored);
    }
}
