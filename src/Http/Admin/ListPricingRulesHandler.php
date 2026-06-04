<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Tenant\AuthContext;

/** GET /admin/pricing-rules (operationId `listPricingRules`). Requires `manage_marketplace`; tenant-scoped. */
final class ListPricingRulesHandler
{
    public function __construct(
        private readonly PricingRuleRepositoryInterface $pricingRules,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $rules = array_map(
            static fn ($r) => $r->toArray(),
            $this->pricingRules->listByOrganization($context->organizationId),
        );

        return $this->json->ok(['pricing_rules' => $rules]);
    }
}
