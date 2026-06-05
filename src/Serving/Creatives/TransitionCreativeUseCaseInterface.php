<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\CreativeScanFailedException;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Serving\UseCase\SelfApprovalForbiddenException;

interface TransitionCreativeUseCaseInterface
{
    /**
     * @throws CreativeNotFoundException
     * @throws InvalidReviewTransitionException
     * @throws SelfApprovalForbiddenException
     * @throws CreativeScanFailedException
     * @throws CreativeValidationException
     */
    public function execute(TransitionCreativeInput $input): TransitionCreativeOutput;
}
