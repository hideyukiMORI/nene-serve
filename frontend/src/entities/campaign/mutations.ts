import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { CampaignDto } from './api-types'
import { mapCampaignDtoToModel } from './mapper'
import type { Campaign } from './model'
import { campaignKeys } from './query-keys'

export interface CreateCampaignInput {
  advertiserId: string
  name: string
  pricingRuleId: string
  budgetCents: number
  pauseOnBudgetExhausted: boolean
}

export function useCreateCampaign(): UseMutationResult<Campaign, AppError, CreateCampaignInput> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<CampaignDto>('/admin/campaigns', {
        advertiser_id: input.advertiserId,
        name: input.name,
        pricing_rule_id: input.pricingRuleId,
        budget_cents: input.budgetCents,
        pause_on_budget_exhausted: input.pauseOnBudgetExhausted,
      })
      return mapCampaignDtoToModel(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: campaignKeys.all })
    },
  })
}
