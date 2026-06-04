import { useAcceptInvitation, useInvitationPreview } from '@/entities/invitation'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface SetPasswordPage {
  validating: boolean
  email: string | null
  invalid: boolean
  submitting: boolean
  submitErrorKey: MessageKey | null
  submit: (password: string) => Promise<boolean>
}

export function useSetPasswordPage(token: string): SetPasswordPage {
  const preview = useInvitationPreview(token)
  const accept = useAcceptInvitation()

  return {
    validating: token !== '' && preview.isPending,
    email: preview.data?.email ?? null,
    invalid: token === '' || preview.error !== null,
    submitting: accept.isPending,
    submitErrorKey: accept.error !== null ? mapProblemDetailsToMessageKey(accept.error) : null,
    submit: async (password: string): Promise<boolean> => {
      try {
        await accept.mutateAsync({ token, password })
        return true
      } catch {
        return false
      }
    },
  }
}
