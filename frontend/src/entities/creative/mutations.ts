import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { CreativeDto } from './api-types'
import { mapCreativeDtoToModel } from './mapper'
import type { Creative } from './model'
import { creativeKeys } from './query-keys'

/** Review-workflow transitions (the state machine + four-eyes live server-side). */
export type ReviewAction = 'start-review' | 'approve' | 'reject' | 'request-changes'

export interface ReviewTransitionInput {
  id: string
  action: ReviewAction
}

export function useReviewTransition(): UseMutationResult<
  Creative,
  AppError,
  ReviewTransitionInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, action }) => {
      const dto = await apiClient.post<CreativeDto>(`/admin/creatives/${id}/${action}`, {})
      return mapCreativeDtoToModel(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: creativeKeys.all })
    },
  })
}
