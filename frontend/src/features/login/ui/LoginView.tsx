import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { LangToggle, ThemeToggle } from '@/app/shell/Toggles'
import type { LoginInput } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { IconArrowRight, IconLock, IconMail, IconStore } from '@/shared/ui/icons'
import { AuthShell } from '@/shared/ui'

export interface LoginViewProps {
  pending: boolean
  errorMessage: string | null
  onSubmit: (input: LoginInput) => Promise<boolean>
  /**
   * The tenant resolved from the URL (subdomain / path / single / custom-domain
   * modes). When set, the organization field is replaced with the resolved
   * organization name; null (login mode) shows the field.
   */
  tenant?: { slug: string; name: string } | null
}

interface LoginFormValues {
  organization: string
  email: string
  password: string
}

export function LoginView({ pending, errorMessage, onSubmit, tenant = null }: LoginViewProps) {
  const { t } = useTranslation()

  const schema = z.object({
    // In a URL mode the org is resolved from the address, not typed.
    organization:
      tenant !== null
        ? z.string()
        : z.string().trim().min(1, t('login.validation.organizationRequired')),
    email: z.string().trim().min(1, t('login.validation.emailRequired')),
    password: z.string().min(1, t('login.validation.passwordRequired')),
  })
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(schema),
    defaultValues: { organization: tenant?.slug ?? '', email: '', password: '' },
  })

  const submit = handleSubmit(async (values) => {
    await onSubmit(values)
  })

  return (
    <AuthShell>
      <form
        noValidate
        onSubmit={(event) => {
          void submit(event)
        }}
        className="stack g5"
      >
        <div className="auth-head">
          <div className="stack g1">
            <h1 className="t-h1">{t('login.title')}</h1>
            <span className="t-cap muted">{t('login.subtitle')}</span>
          </div>
          <div className="auth-controls row g2">
            <ThemeToggle />
            <LangToggle />
          </div>
        </div>

        {errorMessage !== null ? <span className="field-msg err">{errorMessage}</span> : null}

        <div className="stack g4">
          {tenant !== null ? (
            <div className="field">
              <span className="t-cap muted">{t('login.organization')}</span>
              <div className="row g2">
                <span className="pre-icon">
                  <IconStore />
                </span>
                <strong>{t('login.signingInTo', { name: tenant.name })}</strong>
              </div>
              <input type="hidden" {...register('organization')} />
            </div>
          ) : (
            <div className="field">
              <label htmlFor="login-organization">{t('login.organization')}</label>
              <div className="input-wrap">
                <span className="pre-icon">
                  <IconStore />
                </span>
                <input
                  id="login-organization"
                  className="input"
                  type="text"
                  placeholder="acme"
                  {...register('organization')}
                />
              </div>
              {errors.organization?.message !== undefined ? (
                <span className="field-msg err">{errors.organization.message}</span>
              ) : null}
            </div>
          )}
          <div className="field">
            <label htmlFor="login-email">{t('login.email')}</label>
            <div className="input-wrap">
              <span className="pre-icon">
                <IconMail />
              </span>
              <input
                id="login-email"
                className="input"
                type="email"
                placeholder="admin@acme.test"
                {...register('email')}
              />
            </div>
            {errors.email?.message !== undefined ? (
              <span className="field-msg err">{errors.email.message}</span>
            ) : null}
          </div>
          <div className="field">
            <label htmlFor="login-password">{t('login.password')}</label>
            <div className="input-wrap">
              <span className="pre-icon">
                <IconLock />
              </span>
              <input
                id="login-password"
                className="input"
                type="password"
                placeholder="••••••••"
                {...register('password')}
              />
            </div>
            {errors.password?.message !== undefined ? (
              <span className="field-msg err">{errors.password.message}</span>
            ) : null}
          </div>
        </div>

        <button className="btn btn-primary btn-lg btn-block" type="submit" disabled={pending}>
          {t('login.submit')}
          <IconArrowRight />
        </button>
        <div className="row g1 faint t-tiny auth-foot">
          <IconLock />
          <span>{t('login.secure')}</span>
        </div>
      </form>
    </AuthShell>
  )
}
