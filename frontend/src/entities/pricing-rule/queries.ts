import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { PricingRuleListDto } from './api-types'
import { mapPricingRuleDtoToModel } from './mapper'
import type { PricingRule } from './model'
import { pricingRuleKeys } from './query-keys'

export function usePricingRules(): UseQueryResult<PricingRule[], AppError> {
  return useQuery({
    queryKey: pricingRuleKeys.list(),
    queryFn: async () => {
      const dto = await apiClient.get<PricingRuleListDto>('/admin/pricing-rules')
      return dto.pricing_rules.map(mapPricingRuleDtoToModel)
    },
  })
}
