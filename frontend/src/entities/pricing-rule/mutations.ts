import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { PricingRuleDto } from './api-types'
import { mapPricingRuleDtoToModel } from './mapper'
import type { PricingRule } from './model'
import { pricingRuleKeys } from './query-keys'

export interface CreatePricingRuleInput {
  name: string
  model: string
  rateCents: number
}

export function useCreatePricingRule(): UseMutationResult<
  PricingRule,
  AppError,
  CreatePricingRuleInput
> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<PricingRuleDto>('/admin/pricing-rules', {
        name: input.name,
        pricing_model: input.model,
        rate_cents: input.rateCents,
      })
      return mapPricingRuleDtoToModel(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: pricingRuleKeys.all })
    },
  })
}
