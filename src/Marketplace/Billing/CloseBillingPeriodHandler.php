<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** POST /admin/billing-periods/{id}/close (operationId `closeBillingPeriod`). Requires `manage_marketplace`. */
final readonly class CloseBillingPeriodHandler
{
    public function __construct(
        private CloseBillingPeriodUseCaseInterface $close,
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

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        $output = $this->close->execute(new CloseBillingPeriodInput($context->userId, $id));

        return $this->response->create([
            'period' => $output->period->toArray(),
            'snapshot' => $output->snapshot->toArray(),
        ]);
    }
}
