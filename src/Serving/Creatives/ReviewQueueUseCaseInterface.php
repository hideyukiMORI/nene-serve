<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

interface ReviewQueueUseCaseInterface
{
    public function execute(ReviewQueueInput $input): ReviewQueueOutput;
}
