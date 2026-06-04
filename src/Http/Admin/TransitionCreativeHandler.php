<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\Review\ReviewAction;
use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Serving\UseCase\SelfApprovalForbiddenException;
use NeneServe\Serving\UseCase\TransitionCreativeUseCase;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/creatives/{id}/{action} for one fixed review action (operationIds
 * `submitCreative`/`approveCreative`/`rejectCreative`/...). One handler, bound to
 * an action, registered per route — the state-machine rules live in the use case.
 */
final class TransitionCreativeHandler
{
    public function __construct(
        private readonly ReviewAction $action,
        private readonly TransitionCreativeUseCase $transition,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $reason = is_string($body['review_reason'] ?? null) ? $body['review_reason'] : null;
        $override = ($body['self_approval_override'] ?? false) === true;

        try {
            $creative = $this->transition->execute(
                $context,
                (string) $request->param('id'),
                $this->action,
                $reason,
                $override,
            );
        } catch (CreativeNotFoundException) {
            return $this->json->problem(404, 'creative-not-found', 'Creative not found');
        } catch (InvalidReviewTransitionException $e) {
            return $this->json->problem(409, 'invalid-review-transition', 'Invalid review transition', $e->getMessage());
        } catch (SelfApprovalForbiddenException) {
            return $this->json->problem(403, 'self-approval-forbidden', 'Self-approval is not allowed without an audited override');
        } catch (CreativeValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid request', $e->getMessage());
        }

        return $this->json->ok($creative->toAdminArray());
    }
}
