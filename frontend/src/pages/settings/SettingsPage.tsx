import { useTenantContext } from '@/entities/auth'
import { SettingsView, useSettingsPage } from '@/features/settings'
import { useTranslation } from '@/shared/i18n'

export function SettingsPage() {
  const page = useSettingsPage()
  const tenantContext = useTenantContext()
  const { t } = useTranslation()

  const testMessage =
    page.testResultKey === null
      ? null
      : page.testResultKey === 'ok'
        ? t('settings.testOk')
        : t(page.testResultKey)

  return (
    <SettingsView
      settings={page.settings}
      loading={page.loading}
      errorMessage={page.errorKey !== null ? t(page.errorKey) : null}
      saving={page.saving}
      saveErrorMessage={page.saveErrorKey !== null ? t(page.saveErrorKey) : null}
      saved={page.saved}
      testing={page.testing}
      testMessage={testMessage}
      tenant={tenantContext.data ?? null}
      onSave={page.save}
      onTest={() => {
        void page.test()
      }}
    />
  )
}
