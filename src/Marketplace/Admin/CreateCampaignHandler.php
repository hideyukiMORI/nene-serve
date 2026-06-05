<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Http\BodyFieldCollector;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** POST /admin/campaigns (operationId `createCampaign`). Requires `manage_marketplace`. */
final readonly class CreateCampaignHandler
{
    public function __construct(
        private CreateCampaignUseCaseInterface $createCampaign,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $body = JsonRequestBodyParser::parse($request);

        $fields = new BodyFieldCollector($body);
        $advertiserId = $fields->requiredString('advertiser_id', 'Advertiser is required.');
        $name = $fields->requiredString('name', 'Name is required.');
        $pricingRuleId = $fields->requiredString('pricing_rule_id', 'Pricing rule is required.');
        $budgetCents = $fields->requiredInt('budget_cents', 'Budget (cents) must be an integer.');
        $fields->throwIfInvalid();

        $pause = ($body['pause_on_budget_exhausted'] ?? true) !== false;
        $status = isset($body['status']) && is_string($body['status']) ? $body['status'] : 'draft';
        $fundingStatus = isset($body['funding_status']) && is_string($body['funding_status']) ? $body['funding_status'] : 'unfunded';

        $output = $this->createCampaign->execute(
            new CreateCampaignInput($context->userId, $advertiserId, $name, $pricingRuleId, $budgetCents, $pause, $status, $fundingStatus),
        );

        return $this->response->create($output->campaign->toArray(), 201);
    }
}
