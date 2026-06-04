import type { Creative, ReviewAction } from '@/entities/creative'
import { useTranslation } from '@/shared/i18n'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

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
    <Stack gap="md">
      <Stack gap="xs">
        <Text as="h1" variant="heading-md">
          {t('review.title')}
        </Text>
        <Text muted>{t('review.subtitle')}</Text>
        <Text muted variant="caption">
          {t('review.fourEyes')}
        </Text>
      </Stack>

      {loading ? <Text muted>{t('review.loading')}</Text> : null}
      {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}
      {actionErrorMessage !== null ? <Text className="danger">{actionErrorMessage}</Text> : null}

      {!loading && errorMessage === null && creatives.length === 0 ? (
        <EmptyState title={t('review.empty')} />
      ) : null}

      {creatives.length > 0 ? (
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
                <td>{creative.id}</td>
                <td>{creative.type}</td>
                <td>{creative.reviewStatus}</td>
                <td>
                  <Stack direction="horizontal" gap="xs">
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
                  </Stack>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : null}
    </Stack>
  )
}
