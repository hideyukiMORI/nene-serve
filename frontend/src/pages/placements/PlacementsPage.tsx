import { PlacementsView, usePlacementsPage } from '@/features/placements'
import { useTranslation } from '@/shared/i18n'

export function PlacementsPage() {
  const page = usePlacementsPage()
  const { t } = useTranslation()

  return (
    <PlacementsView
      placements={page.placements}
      loading={page.loading}
      errorMessage={page.errorKey !== null ? t(page.errorKey) : null}
    />
  )
}
