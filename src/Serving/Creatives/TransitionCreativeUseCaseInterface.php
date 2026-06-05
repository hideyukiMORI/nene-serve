<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\Creative;
use NeneServe\Serving\Review\ReviewAction;
use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\CreativeScanFailedException;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Serving\UseCase\SelfApprovalForbiddenException;
use NeneServe\Tenant\AuthContext;

interface TransitionCreativeUseCaseInterface
{
    /**
     * @throws CreativeNotFoundException
     * @throws InvalidReviewTransitionException
     * @throws SelfApprovalForbiddenException
     * @throws CreativeScanFailedException
     * @throws CreativeValidationException
     */
    public function execute(
        AuthContext $actor,
        string $creativeId,
        ReviewAction $action,
        ?string $reason = null,
        bool $selfApprovalOverride = false,
    ): Creative;
}
