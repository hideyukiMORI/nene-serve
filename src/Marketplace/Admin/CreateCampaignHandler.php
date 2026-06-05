<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
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

        $advertiserId = isset($body['advertiser_id']) && is_string($body['advertiser_id']) ? $body['advertiser_id'] : '';
        $name = isset($body['name']) && is_string($body['name']) ? $body['name'] : '';
        $pricingRuleId = isset($body['pricing_rule_id']) && is_string($body['pricing_rule_id']) ? $body['pricing_rule_id'] : '';
        $budgetCents = $body['budget_cents'] ?? null;

        $errors = [];

        if ($advertiserId === '') {
            $errors[] = new ValidationError('advertiser_id', 'Advertiser is required.', 'required');
        }

        if ($name === '') {
            $errors[] = new ValidationError('name', 'Name is required.', 'required');
        }

        if ($pricingRuleId === '') {
            $errors[] = new ValidationError('pricing_rule_id', 'Pricing rule is required.', 'required');
        }

        if (!is_int($budgetCents)) {
            $errors[] = new ValidationError('budget_cents', 'Budget (cents) must be an integer.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var int $budgetCents */
        $pause = ($body['pause_on_budget_exhausted'] ?? true) !== false;
        $status = isset($body['status']) && is_string($body['status']) ? $body['status'] : 'draft';
        $fundingStatus = isset($body['funding_status']) && is_string($body['funding_status']) ? $body['funding_status'] : 'unfunded';

        $output = $this->createCampaign->execute(
            new CreateCampaignInput($context->userId, $advertiserId, $name, $pricingRuleId, $budgetCents, $pause, $status, $fundingStatus),
        );

        return $this->response->create($output->campaign->toArray(), 201);
    }
}
