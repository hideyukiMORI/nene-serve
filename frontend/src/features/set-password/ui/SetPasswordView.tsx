import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Text } from '@/shared/ui'

export interface SetPasswordViewProps {
  validating: boolean
  email: string | null
  invalid: boolean
  submitting: boolean
  errorMessage: string | null
  onSubmit: (password: string) => Promise<boolean>
}

interface Values {
  password: string
}

export function SetPasswordView({
  validating,
  email,
  invalid,
  submitting,
  errorMessage,
  onSubmit,
}: SetPasswordViewProps) {
  const { t } = useTranslation()
  const schema = z.object({ password: z.string().min(8, t('setPassword.tooShort')) })
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<Values>({ resolver: zodResolver(schema), defaultValues: { password: '' } })

  const submit = handleSubmit(async (values) => {
    await onSubmit(values.password)
  })

  return (
    <div className="fs-wrap">
      <div className="auth-form" style={{ minHeight: '100vh' }}>
        <div className="auth-card stack g6">
          <div className="stack g2">
            <h1 className="t-h1">{t('setPassword.title')}</h1>
            {email !== null ? <span className="muted t-cap">{email}</span> : null}
          </div>

          {validating ? <Text muted>{t('setPassword.validating')}</Text> : null}
          {invalid && !validating ? (
            <Text className="danger">{t('setPassword.invalid')}</Text>
          ) : null}

          {!invalid && !validating ? (
            <form
              noValidate
              onSubmit={(event) => {
                void submit(event)
              }}
              className="stack g4"
            >
              <Input
                id="set-password"
                type="password"
                label={t('setPassword.password')}
                error={errors.password?.message}
                {...register('password')}
              />
              {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}
              <Button type="submit" disabled={submitting}>
                {submitting ? t('form.creating') : t('setPassword.submit')}
              </Button>
            </form>
          ) : null}
        </div>
      </div>
    </div>
  )
}
