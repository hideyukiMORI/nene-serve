import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { InvitationPreviewDto } from './api-types'
import { invitationKeys } from './query-keys'

/** Validate an invitation token (unauthenticated); returns the invitee email. */
export function useInvitationPreview(
  token: string,
): UseQueryResult<InvitationPreviewDto, AppError> {
  return useQuery({
    queryKey: invitationKeys.preview(token),
    queryFn: () =>
      apiClient.get<InvitationPreviewDto>(`/admin/invitations/${encodeURIComponent(token)}`),
    enabled: token !== '',
    retry: false,
  })
}
