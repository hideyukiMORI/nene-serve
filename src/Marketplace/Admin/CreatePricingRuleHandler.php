<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Http\BodyFieldCollector;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** POST /admin/pricing-rules (operationId `createPricingRule`). Requires `manage_marketplace`. */
final readonly class CreatePricingRuleHandler
{
    public function __construct(
        private CreatePricingRuleUseCaseInterface $createPricingRule,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $body = JsonRequestBodyParser::parse($request);

        $fields = new BodyFieldCollector($body);
        $name = $fields->requiredString('name', 'Name is required.');
        $model = $fields->requiredString('pricing_model', 'Pricing model is required.');
        $rateCents = $fields->requiredInt('rate_cents', 'Rate (cents) must be an integer.');
        $fields->throwIfInvalid();

        $output = $this->createPricingRule->execute(new CreatePricingRuleInput($context->userId, $name, $model, $rateCents));

        return $this->response->create($output->pricingRule->toArray(), 201);
    }
}
