<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneServe\Serving\Creative;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/review-queue (operationId `listReviewQueue`). Requires
 * `review_creatives`. Lists the caller's tenant creatives awaiting a review
 * decision (`submitted` / `in_review`).
 */
final readonly class ReviewQueueHandler
{
    public function __construct(
        private ReviewQueueUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ReviewQueueInput($pagination->limit, $pagination->offset));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(static fn (Creative $creative): array => $creative->toAdminArray(), $output->items),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}
