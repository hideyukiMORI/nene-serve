import { useInviteUser, useUsers, type User } from '@/entities/user'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface UsersPage {
  users: User[]
  loading: boolean
  errorKey: MessageKey | null
  inviting: boolean
  inviteErrorKey: MessageKey | null
  lastInviteEmailSent: boolean | null
  invite: (email: string, role: string) => Promise<boolean>
}

export function useUsersPage(): UsersPage {
  const query = useUsers()
  const inviteMutation = useInviteUser()

  return {
    users: query.data ?? [],
    loading: query.isPending,
    errorKey: query.error !== null ? mapProblemDetailsToMessageKey(query.error) : null,
    inviting: inviteMutation.isPending,
    inviteErrorKey:
      inviteMutation.error !== null ? mapProblemDetailsToMessageKey(inviteMutation.error) : null,
    lastInviteEmailSent: inviteMutation.data?.invite_email_sent ?? null,
    invite: async (email: string, role: string): Promise<boolean> => {
      try {
        await inviteMutation.mutateAsync({ email, role })
        return true
      } catch {
        return false
      }
    },
  }
}
