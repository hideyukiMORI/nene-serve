import type { Creative, ReviewAction } from '@/entities/creative'
import { useTranslation } from '@/shared/i18n'
import { Button, EmptyState, Page, Pill, reviewStatusTone, Stack, Text } from '@/shared/ui'

export interface ReviewViewProps {
  creatives: Creative[]
  loading: boolean
  errorMessage: string | null
  acting: boolean
  actionErrorMessage: string | null
  onAct: (id: string, action: ReviewAction) => void
}

const ACTIONS: {
  action: ReviewAction
  labelKey:
    | 'review.action.startReview'
    | 'review.action.approve'
    | 'review.action.reject'
    | 'review.action.requestChanges'
  variant: 'primary' | 'secondary' | 'danger'
}[] = [
  { action: 'start-review', labelKey: 'review.action.startReview', variant: 'secondary' },
  { action: 'approve', labelKey: 'review.action.approve', variant: 'primary' },
  { action: 'request-changes', labelKey: 'review.action.requestChanges', variant: 'secondary' },
  { action: 'reject', labelKey: 'review.action.reject', variant: 'danger' },
]

export function ReviewView({
  creatives,
  loading,
  errorMessage,
  acting,
  actionErrorMessage,
  onAct,
}: ReviewViewProps) {
  const { t } = useTranslation()

  return (
    <Page title={t('review.title')} subtitle={t('review.subtitle')}>
      <Stack gap="md">
        <div className="callout callout-info">
          <div>{t('review.fourEyes')}</div>
        </div>

        {loading ? <Text muted>{t('review.loading')}</Text> : null}
        {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}
        {actionErrorMessage !== null ? <Text className="danger">{actionErrorMessage}</Text> : null}

        {!loading && errorMessage === null && creatives.length === 0 ? (
          <EmptyState title={t('review.empty')} />
        ) : null}

        {creatives.length > 0 ? (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>{t('review.column.id')}</th>
                  <th>{t('review.column.type')}</th>
                  <th>{t('review.column.status')}</th>
                  <th>{t('review.column.actions')}</th>
                </tr>
              </thead>
              <tbody>
                {creatives.map((creative) => (
                  <tr key={creative.id}>
                    <td className="cell-key">{creative.id}</td>
                    <td>{creative.type}</td>
                    <td>
                      <Pill tone={reviewStatusTone(creative.reviewStatus)}>
                        {creative.reviewStatus}
                      </Pill>
                    </td>
                    <td>
                      <div className="row g1 wrap">
                        {ACTIONS.map(({ action, labelKey, variant }) => (
                          <Button
                            key={action}
                            variant={variant}
                            size="sm"
                            disabled={acting}
                            onClick={() => {
                              onAct(creative.id, action)
                            }}
                          >
                            {t(labelKey)}
                          </Button>
                        ))}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : null}
      </Stack>
    </Page>
  )
}
