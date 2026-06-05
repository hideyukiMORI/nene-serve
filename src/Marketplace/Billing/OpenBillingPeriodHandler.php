<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Http\BodyFieldCollector;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** POST /admin/campaigns/{id}/billing-periods (operationId `openBillingPeriod`). Requires `manage_marketplace`. */
final readonly class OpenBillingPeriodHandler
{
    public function __construct(
        private OpenBillingPeriodUseCaseInterface $open,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $campaignId = Router::param($request, 'id') ?? '';

        $body = JsonRequestBodyParser::parse($request);
        $fields = new BodyFieldCollector($body);
        $start = $fields->requiredString('period_start', 'period_start (YYYY-MM-DD) is required.');
        $end = $fields->requiredString('period_end', 'period_end (YYYY-MM-DD) is required.');
        $fields->throwIfInvalid();

        $period = $this->open->execute(new OpenBillingPeriodInput($context->userId, $campaignId, $start, $end))->period;

        return $this->response->create($period->toArray(), 201);
    }
}
