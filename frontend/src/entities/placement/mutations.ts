import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { PlacementDto } from './api-types'
import { mapPlacementDtoToModel } from './mapper'
import type { Placement } from './model'
import { placementKeys } from './query-keys'

export interface CreatePlacementInput {
  publicKey: string
  allowedOrigins: string[]
  defaultCreativeId: string | null
}

export function useCreatePlacement(): UseMutationResult<Placement, AppError, CreatePlacementInput> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<PlacementDto>('/admin/placements', {
        public_placement_key: input.publicKey,
        allowed_origins: input.allowedOrigins,
        default_creative_id: input.defaultCreativeId,
      })
      return mapPlacementDtoToModel(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: placementKeys.all })
    },
  })
}
