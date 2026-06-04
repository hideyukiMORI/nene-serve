import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { AdvertiserDto } from './api-types'
import { mapAdvertiserDtoToModel } from './mapper'
import type { Advertiser } from './model'
import { advertiserKeys } from './query-keys'

export interface CreateAdvertiserInput {
  name: string
}

export function useCreateAdvertiser(): UseMutationResult<
  Advertiser,
  AppError,
  CreateAdvertiserInput
> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<AdvertiserDto>('/admin/advertisers', { name: input.name })
      return mapAdvertiserDtoToModel(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: advertiserKeys.all })
    },
  })
}
