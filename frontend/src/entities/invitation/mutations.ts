import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'

export interface AcceptInvitationInput {
  token: string
  password: string
}

export function useAcceptInvitation(): UseMutationResult<
  { accepted: boolean },
  AppError,
  AcceptInvitationInput
> {
  return useMutation({
    mutationFn: (input) =>
      apiClient.post<{ accepted: boolean }>('/admin/invitations/accept', {
        token: input.token,
        password: input.password,
      }),
  })
}
