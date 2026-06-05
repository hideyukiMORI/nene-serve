<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $id = Router::param($request, 'id') ?? '';

        $output = $this->close->execute(new CloseBillingPeriodInput($context->userId, $id));

        return $this->response->create([
            'period' => $output->period->toArray(),
            'snapshot' => $output->snapshot->toArray(),
        ]);
    }
}
