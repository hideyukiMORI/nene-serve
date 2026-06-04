import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { AdvertiserListDto } from './api-types'
import { mapAdvertiserDtoToModel } from './mapper'
import type { Advertiser } from './model'
import { advertiserKeys } from './query-keys'

export function useAdvertisers(): UseQueryResult<Advertiser[], AppError> {
  return useQuery({
    queryKey: advertiserKeys.list(),
    queryFn: async () => {
      const dto = await apiClient.get<AdvertiserListDto>('/admin/advertisers')
      return dto.advertisers.map(mapAdvertiserDtoToModel)
    },
  })
}
