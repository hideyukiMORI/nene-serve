import { MetricsView, useMetricsPage } from '@/features/metrics'
import { useTranslation } from '@/shared/i18n'

export function MetricsPage() {
  const page = useMetricsPage()
  const { t } = useTranslation()

  return (
    <MetricsView
      report={page.report}
      loading={page.loading}
      errorMessage={page.errorKey !== null ? t(page.errorKey) : null}
    />
  )
}
