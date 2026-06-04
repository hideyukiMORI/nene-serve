<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Tenant\AuthContext;

/**
 * GET /admin/review-queue (operationId `listReviewQueue`). Requires
 * `review_creatives`. Lists the caller's tenant creatives awaiting a review
 * decision (`submitted` / `in_review`).
 */
final class ReviewQueueHandler
{
    private const REVIEWABLE = [ReviewStatus::Submitted, ReviewStatus::InReview];

    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $queue = array_values(array_filter(
            $this->creatives->listByOrganization($context->organizationId),
            static fn ($c): bool => in_array($c->reviewStatus, self::REVIEWABLE, true),
        ));

        return $this->json->ok([
            'creatives' => array_map(static fn ($c) => $c->toAdminArray(), $queue),
        ]);
    }
}
