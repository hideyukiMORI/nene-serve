import { UsersView, useUsersPage } from '@/features/users'
import { useTranslation } from '@/shared/i18n'

export function UsersPage() {
  const page = useUsersPage()
  const { t } = useTranslation()

  return (
    <UsersView
      users={page.users}
      loading={page.loading}
      errorMessage={page.errorKey !== null ? t(page.errorKey) : null}
      inviting={page.inviting}
      inviteErrorMessage={page.inviteErrorKey !== null ? t(page.inviteErrorKey) : null}
      inviteEmailSent={page.lastInviteEmailSent}
      onInvite={page.invite}
    />
  )
}
