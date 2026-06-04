import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useTranslation } from '@/shared/i18n'
import { AuthShell, Text } from '@/shared/ui'

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
    <AuthShell>
      <div className="stack g5">
        <div className="stack g1">
          <h1 className="t-h1">{t('setPassword.title')}</h1>
          {email !== null ? <span className="t-cap muted mono">{email}</span> : null}
        </div>

        {validating ? <Text muted>{t('setPassword.validating')}</Text> : null}
        {invalid && !validating ? (
          <span className="field-msg err">{t('setPassword.invalid')}</span>
        ) : null}

        {!invalid && !validating ? (
          <form
            noValidate
            onSubmit={(event) => {
              void submit(event)
            }}
            className="stack g4"
          >
            <div className="field">
              <label htmlFor="set-password">{t('setPassword.password')}</label>
              <input
                id="set-password"
                className="input"
                type="password"
                placeholder="••••••••"
                {...register('password')}
              />
              {errors.password?.message !== undefined ? (
                <span className="field-msg err">{errors.password.message}</span>
              ) : null}
            </div>
            {errorMessage !== null ? <span className="field-msg err">{errorMessage}</span> : null}
            <button
              className="btn btn-primary btn-lg btn-block"
              type="submit"
              disabled={submitting}
            >
              {submitting ? t('form.creating') : t('setPassword.submit')}
            </button>
          </form>
        ) : null}
      </div>
    </AuthShell>
  )
}
