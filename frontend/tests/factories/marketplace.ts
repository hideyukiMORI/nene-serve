import type { AdvertiserDto } from '@/entities/advertiser/api-types'
import type { CampaignDto } from '@/entities/campaign/api-types'
import type { PricingRuleDto } from '@/entities/pricing-rule/api-types'

export function makeAdvertiserDto(overrides: Partial<AdvertiserDto> = {}): AdvertiserDto {
  return { id: 'adv-1', name: 'Acme Co', status: 'active', invoice_client_id: null, ...overrides }
}

export function makePricingRuleDto(overrides: Partial<PricingRuleDto> = {}): PricingRuleDto {
  return {
    id: 'pr-1',
    name: 'CPM standard',
    pricing_model: 'cpm',
    rate_cents: 50000,
    currency: 'JPY',
    pricing_rule_version: 1,
    created_at: '2026-01-01',
    ...overrides,
  }
}

export function makeCampaignDto(overrides: Partial<CampaignDto> = {}): CampaignDto {
  return {
    id: 'cmp-1',
    advertiser_id: 'adv-1',
    name: 'Spring push',
    pricing_rule_id: 'pr-1',
    budget_cents: 1000000,
    currency: 'JPY',
    status: 'active',
    funding_status: 'funded',
    pause_on_budget_exhausted: true,
    ...overrides,
  }
}
