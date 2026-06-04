import { CreativesView, useCreativesPage } from '@/features/creatives'
import { useTranslation } from '@/shared/i18n'

export function CreativesPage() {
  const page = useCreativesPage()
  const { t } = useTranslation()

  return (
    <CreativesView
      creatives={page.creatives}
      loading={page.loading}
      errorMessage={page.errorKey !== null ? t(page.errorKey) : null}
    />
  )
}
