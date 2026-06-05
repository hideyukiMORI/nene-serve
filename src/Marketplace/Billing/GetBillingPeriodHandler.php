<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Marketplace\BillingPeriodRepositoryInterface;
use NeneServe\Marketplace\SpendSnapshotRepositoryInterface;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/billing-periods/{id} (operationId `getBillingPeriod`). Requires `manage_marketplace`. */
final readonly class GetBillingPeriodHandler
{
    public function __construct(
        private BillingPeriodRepositoryInterface $periods,
        private SpendSnapshotRepositoryInterface $snapshots,
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

        $period = $this->periods->findByIdInOrganization($id, $context->organizationId);

        if ($period === null) {
            return $this->problemDetails->create($request, 'billing-period-not-found', 'Billing period not found', 404, 'No billing period with that id.');
        }

        $latest = $this->snapshots->latestForPeriod($context->organizationId, $period->id);

        return $this->response->create($period->toArray() + ['latest_snapshot' => $latest?->toArray()]);
    }
}
