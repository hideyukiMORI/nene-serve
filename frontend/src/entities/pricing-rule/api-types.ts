import type { Paginated } from '@/shared/api/pagination'

export interface PricingRuleDto {
  id: string
  name: string
  pricing_model: string
  rate_cents: number
  currency: string
  pricing_rule_version: number
  created_at: string
}

export type PricingRuleListDto = Paginated<PricingRuleDto>
