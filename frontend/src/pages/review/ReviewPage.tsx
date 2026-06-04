import { ReviewView, useReviewPage } from '@/features/review'
import { useTranslation } from '@/shared/i18n'

export function ReviewPage() {
  const page = useReviewPage()
  const { t } = useTranslation()

  return (
    <ReviewView
      creatives={page.creatives}
      loading={page.loading}
      errorMessage={page.errorKey !== null ? t(page.errorKey) : null}
      acting={page.acting}
      actionErrorMessage={page.actionErrorKey !== null ? t(page.actionErrorKey) : null}
      onAct={(id, action) => {
        void page.act(id, action)
      }}
    />
  )
}
