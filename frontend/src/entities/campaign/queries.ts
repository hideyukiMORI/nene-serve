import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { CampaignListDto } from './api-types'
import { mapCampaignDtoToModel } from './mapper'
import type { Campaign } from './model'
import { campaignKeys } from './query-keys'

export function useCampaigns(): UseQueryResult<Campaign[], AppError> {
  return useQuery({
    queryKey: campaignKeys.list(),
    queryFn: async () => {
      const dto = await apiClient.get<CampaignListDto>('/admin/campaigns')
      return dto.campaigns.map(mapCampaignDtoToModel)
    },
  })
}
