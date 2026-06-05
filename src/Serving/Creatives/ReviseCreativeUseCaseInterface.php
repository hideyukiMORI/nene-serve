<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\CreativeValidationException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;

interface ReviseCreativeUseCaseInterface
{
    /**
     * @throws CreativeNotFoundException
     * @throws InvalidReviewTransitionException
     * @throws CreativeValidationException
     */
    public function execute(ReviseCreativeInput $input): ReviseCreativeOutput;
}
