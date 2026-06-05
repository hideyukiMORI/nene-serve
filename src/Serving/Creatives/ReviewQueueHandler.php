<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/review-queue (operationId `listReviewQueue`). Requires
 * `review_creatives`. Lists the caller's tenant creatives awaiting a review
 * decision (`submitted` / `in_review`).
 */
final readonly class ReviewQueueHandler
{
    private const REVIEWABLE = [ReviewStatus::Submitted, ReviewStatus::InReview];

    public function __construct(
        private CreativeRepositoryInterface $creatives,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        $queue = array_values(array_filter(
            $this->creatives->listByOrganization($context->organizationId),
            static fn ($creative): bool => in_array($creative->reviewStatus, self::REVIEWABLE, true),
        ));

        return $this->response->create([
            'creatives' => array_map(static fn ($creative) => $creative->toAdminArray(), $queue),
        ]);
    }
}
