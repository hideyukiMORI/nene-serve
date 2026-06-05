<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/pricing-rules (operationId `listPricingRules`). Requires `manage_marketplace`; tenant-scoped. */
final readonly class ListPricingRulesHandler
{
    public function __construct(
        private PricingRuleRepositoryInterface $rules,
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

        return $this->response->create([
            'pricing_rules' => array_map(
                static fn ($rule) => $rule->toArray(),
                $this->rules->listByOrganization($context->organizationId),
            ),
        ]);
    }
}
