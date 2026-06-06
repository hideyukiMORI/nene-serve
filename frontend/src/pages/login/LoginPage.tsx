import { useNavigate } from 'react-router-dom'
import { useTenantContext } from '@/entities/auth'
import { LoginView, useLoginPage } from '@/features/login'
import { useTranslation } from '@/shared/i18n'

export function LoginPage() {
  const page = useLoginPage()
  const tenantContext = useTenantContext()
  const navigate = useNavigate()
  const { t } = useTranslation()

  return (
    <LoginView
      pending={page.pending}
      errorMessage={page.errorKey !== null ? t('login.failed') : null}
      tenant={tenantContext.data?.organization ?? null}
      onSubmit={async (input) => {
        const ok = await page.submit(input)
        if (ok) {
          void navigate('/')
        }
        return ok
      }}
    />
  )
}
