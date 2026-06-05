<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Serving\CreativeRepositoryInterface;

final readonly class ReviewQueueUseCase implements ReviewQueueUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private CreativeRepositoryInterface $creatives,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(ReviewQueueInput $input): ReviewQueueOutput
    {
        return new ReviewQueueOutput(
            items: $this->creatives->listReviewQueue($this->organizationId->get(), $input->limit, $input->offset),
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
