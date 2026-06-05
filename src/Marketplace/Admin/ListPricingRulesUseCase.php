<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;

final readonly class ListPricingRulesUseCase implements ListPricingRulesUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private PricingRuleRepositoryInterface $rules,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(ListPricingRulesInput $input): ListPricingRulesOutput
    {
        return new ListPricingRulesOutput(
            items: $this->rules->listByOrganization($this->organizationId->get(), $input->limit, $input->offset),
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
