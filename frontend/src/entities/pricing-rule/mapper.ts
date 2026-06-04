import type { PricingRuleDto } from './api-types'
import type { PricingRule } from './model'

export function mapPricingRuleDtoToModel(dto: PricingRuleDto): PricingRule {
  return {
    id: dto.id,
    name: dto.name,
    model: dto.pricing_model,
    rateCents: dto.rate_cents,
    version: dto.pricing_rule_version,
  }
}
