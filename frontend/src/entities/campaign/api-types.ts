import type { Paginated } from '@/shared/api/pagination'

export interface CampaignDto {
  id: string
  advertiser_id: string
  name: string
  pricing_rule_id: string
  budget_cents: number
  currency: string
  status: string
  funding_status: string
  pause_on_budget_exhausted: boolean
}

export type CampaignListDto = Paginated<CampaignDto>
