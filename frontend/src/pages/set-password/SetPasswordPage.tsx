import { useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { SetPasswordView, useSetPasswordPage } from '@/features/set-password'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'

export function SetPasswordPage() {
  const [params] = useSearchParams()
  const token = params.get('token') ?? ''
  const page = useSetPasswordPage(token)
  const navigate = useNavigate()
  const { t } = useTranslation()
  const [done, setDone] = useState(false)

  if (done) {
    return (
      <div className="fs-wrap">
        <div className="auth-form" style={{ minHeight: '100vh' }}>
          <div className="auth-card stack g4">
            <h1 className="t-h1">{t('setPassword.doneTitle')}</h1>
            <Text muted>{t('setPassword.doneBody')}</Text>
            <button
              type="button"
              className="btn btn-primary"
              onClick={() => {
                void navigate('/login')
              }}
            >
              {t('setPassword.toLogin')}
            </button>
          </div>
        </div>
      </div>
    )
  }

  return (
    <SetPasswordView
      validating={page.validating}
      email={page.email}
      invalid={page.invalid}
      submitting={page.submitting}
      errorMessage={page.submitErrorKey !== null ? t(page.submitErrorKey) : null}
      onSubmit={async (password) => {
        const ok = await page.submit(password)
        if (ok) setDone(true)
        return ok
      }}
    />
  )
}
