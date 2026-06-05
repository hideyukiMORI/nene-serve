import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { CreativeListDto } from './api-types'
import { mapCreativeDtoToModel } from './mapper'
import type { Creative } from './model'
import { creativeKeys } from './query-keys'

/** Lists the caller's creatives (admin surface). */
export function useCreatives(): UseQueryResult<Creative[], AppError> {
  return useQuery({
    queryKey: creativeKeys.list(),
    queryFn: async () => {
      const dto = await apiClient.get<CreativeListDto>('/admin/creatives')
      return dto.items.map(mapCreativeDtoToModel)
    },
  })
}

/** Creatives awaiting a review decision (submitted / in_review). */
export function useReviewQueue(): UseQueryResult<Creative[], AppError> {
  return useQuery({
    queryKey: creativeKeys.reviewQueue(),
    queryFn: async () => {
      const dto = await apiClient.get<CreativeListDto>('/admin/review-queue')
      return dto.items.map(mapCreativeDtoToModel)
    },
  })
}
