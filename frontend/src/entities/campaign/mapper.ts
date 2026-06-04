import type { CampaignDto } from './api-types'
import type { Campaign } from './model'

export function mapCampaignDtoToModel(dto: CampaignDto): Campaign {
  return {
    id: dto.id,
    name: dto.name,
    status: dto.status,
    fundingStatus: dto.funding_status,
    budgetCents: dto.budget_cents,
  }
}
