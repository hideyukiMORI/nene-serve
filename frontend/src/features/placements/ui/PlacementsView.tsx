import type { Placement } from '@/entities/placement'
import { useTranslation } from '@/shared/i18n'
import { EmptyState, lifecycleTone, Page, Pill, Stack, Text } from '@/shared/ui'
import { CreatePlacementForm } from './CreatePlacementForm'

export interface PlacementsViewProps {
  placements: Placement[]
  loading: boolean
  errorMessage: string | null
}

export function PlacementsView({ placements, loading, errorMessage }: PlacementsViewProps) {
  const { t } = useTranslation()

  return (
    <Page title={t('placements.title')} subtitle={t('placements.subtitle')}>
      <Stack gap="md">
        <CreatePlacementForm />

        {loading ? <Text muted>{t('placements.loading')}</Text> : null}
        {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}

        {!loading && errorMessage === null && placements.length === 0 ? (
          <EmptyState title={t('placements.empty')} />
        ) : null}

        {placements.length > 0 ? (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>{t('placements.column.key')}</th>
                  <th>{t('placements.column.status')}</th>
                  <th>{t('placements.column.creative')}</th>
                </tr>
              </thead>
              <tbody>
                {placements.map((placement) => (
                  <tr key={placement.id}>
                    <td className="cell-key">{placement.publicKey}</td>
                    <td>
                      <Pill tone={lifecycleTone(placement.status)}>{placement.status}</Pill>
                    </td>
                    <td>{placement.defaultCreativeId ?? '—'}</td>
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
