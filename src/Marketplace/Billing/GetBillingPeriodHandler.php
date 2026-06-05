<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/billing-periods/{id} (operationId `getBillingPeriod`). Requires `manage_marketplace`. */
final readonly class GetBillingPeriodHandler
{
    public function __construct(
        private GetBillingPeriodUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        $output = $this->useCase->execute(new GetBillingPeriodInput($id));

        return $this->response->create($output->period->toArray() + ['latest_snapshot' => $output->latestSnapshot?->toArray()]);
    }
}
