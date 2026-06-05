<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** POST /admin/pricing-rules (operationId `createPricingRule`). Requires `manage_marketplace`. */
final readonly class CreatePricingRuleHandler
{
    public function __construct(
        private CreatePricingRuleUseCaseInterface $createPricingRule,
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

        $body = JsonRequestBodyParser::parse($request);

        $name = isset($body['name']) && is_string($body['name']) ? $body['name'] : '';
        $model = isset($body['pricing_model']) && is_string($body['pricing_model']) ? $body['pricing_model'] : '';
        $rateCents = $body['rate_cents'] ?? null;

        $errors = [];

        if ($name === '') {
            $errors[] = new ValidationError('name', 'Name is required.', 'required');
        }

        if ($model === '') {
            $errors[] = new ValidationError('pricing_model', 'Pricing model is required.', 'required');
        }

        if (!is_int($rateCents)) {
            $errors[] = new ValidationError('rate_cents', 'Rate (cents) must be an integer.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var int $rateCents */
        $output = $this->createPricingRule->execute(new CreatePricingRuleInput($context->userId, $name, $model, $rateCents));

        return $this->response->create($output->pricingRule->toArray(), 201);
    }
}
