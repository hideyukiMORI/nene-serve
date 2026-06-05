import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { PlacementListDto } from './api-types'
import { mapPlacementDtoToModel } from './mapper'
import type { Placement } from './model'
import { placementKeys } from './query-keys'

/** Lists the caller's placements (admin surface). */
export function usePlacements(): UseQueryResult<Placement[], AppError> {
  return useQuery({
    queryKey: placementKeys.list(),
    queryFn: async () => {
      const dto = await apiClient.get<PlacementListDto>('/admin/placements')
      return dto.items.map(mapPlacementDtoToModel)
    },
  })
}
