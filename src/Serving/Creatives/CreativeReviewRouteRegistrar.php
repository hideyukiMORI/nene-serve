<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\Review\ReviewAction;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers the five review-transition routes, each bound to a fixed
 * {@see ReviewAction}. Author actions (submit) require `manage_creatives`;
 * reviewer actions require `review_creatives` (enforced by CapabilityResolver).
 */
final readonly class CreativeReviewRouteRegistrar
{
    public function __construct(
        private TransitionCreativeUseCaseInterface $transition,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $actions = [
            'submit' => ReviewAction::Submit,
            'start-review' => ReviewAction::StartReview,
            'approve' => ReviewAction::Approve,
            'reject' => ReviewAction::Reject,
            'request-changes' => ReviewAction::RequestChanges,
        ];

        foreach ($actions as $path => $action) {
            $handler = new TransitionCreativeHandler($action, $this->transition, $this->response, $this->problemDetails);
            $router->post(
                '/admin/creatives/{id}/' . $path,
                static fn (ServerRequestInterface $request) => $handler->handle($request),
            );
        }
    }
}
