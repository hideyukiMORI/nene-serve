import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { User } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, EmptyState, Input, Page, Select, Stack, Text } from '@/shared/ui'

export interface UsersViewProps {
  users: User[]
  loading: boolean
  errorMessage: string | null
  inviting: boolean
  inviteErrorMessage: string | null
  inviteEmailSent: boolean | null
  onInvite: (email: string, role: string) => Promise<boolean>
}

interface InviteValues {
  email: string
  role: string
}

const ROLES = ['org_admin', 'editor', 'analyst']

export function UsersView({
  users,
  loading,
  errorMessage,
  inviting,
  inviteErrorMessage,
  inviteEmailSent,
  onInvite,
}: UsersViewProps) {
  const { t } = useTranslation()
  const schema = z.object({
    email: z.string().trim().min(1, t('form.required')),
    role: z.string().min(1, t('form.required')),
  })
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<InviteValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', role: 'analyst' },
  })

  const submit = handleSubmit(async (values) => {
    const ok = await onInvite(values.email, values.role)
    if (ok) reset()
  })

  return (
    <Page title={t('users.title')} subtitle={t('users.subtitle')}>
      <Stack gap="md">
        <form
          noValidate
          onSubmit={(event) => {
            void submit(event)
          }}
          className="card stack g3"
        >
          <Text as="h3" variant="heading-sm">
            {t('users.invite')}
          </Text>
          <div className="row g3 wrap">
            <Input
              id="invite-email"
              type="email"
              label={t('users.field.email')}
              error={errors.email?.message}
              {...register('email')}
            />
            <Select
              id="invite-role"
              label={t('users.field.role')}
              options={ROLES.map((r) => ({ value: r, label: r }))}
              error={errors.role?.message}
              {...register('role')}
            />
            <Button type="submit" disabled={inviting}>
              {inviting ? t('form.creating') : t('users.action.invite')}
            </Button>
          </div>
          {inviteErrorMessage !== null ? (
            <Text className="danger">{inviteErrorMessage}</Text>
          ) : null}
          {inviteEmailSent === true ? (
            <Text muted variant="caption">
              {t('users.inviteSent')}
            </Text>
          ) : null}
          {inviteEmailSent === false ? (
            <Text className="danger">{t('users.inviteNotSent')}</Text>
          ) : null}
        </form>

        {loading ? <Text muted>{t('users.loading')}</Text> : null}
        {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}
        {!loading && errorMessage === null && users.length === 0 ? (
          <EmptyState title={t('users.empty')} />
        ) : null}

        {users.length > 0 ? (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>{t('users.column.email')}</th>
                  <th>{t('users.column.role')}</th>
                </tr>
              </thead>
              <tbody>
                {users.map((user) => (
                  <tr key={user.id}>
                    <td>{user.email}</td>
                    <td>{user.role}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : null}
      </Stack>
    </Page>
  )
}
