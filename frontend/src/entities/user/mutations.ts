import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { InviteUserResultDto } from './api-types'
import { userKeys } from './query-keys'

export interface InviteUserInput {
  email: string
  role: string
}

export function useInviteUser(): UseMutationResult<InviteUserResultDto, AppError, InviteUserInput> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input) =>
      apiClient.post<InviteUserResultDto>('/admin/users', { email: input.email, role: input.role }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: userKeys.all })
    },
  })
}
