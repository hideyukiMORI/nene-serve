import type { Placement } from '@/entities/placement'
import { useTranslation } from '@/shared/i18n'
import { EmptyState, Stack, Text } from '@/shared/ui'

export interface PlacementsViewProps {
  placements: Placement[]
  loading: boolean
  errorMessage: string | null
}

export function PlacementsView({ placements, loading, errorMessage }: PlacementsViewProps) {
  const { t } = useTranslation()

  return (
    <Stack gap="md">
      <Stack gap="xs">
        <Text as="h1" variant="heading-md">
          {t('placements.title')}
        </Text>
        <Text muted>{t('placements.subtitle')}</Text>
      </Stack>

      {loading ? <Text muted>{t('placements.loading')}</Text> : null}
      {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}

      {!loading && errorMessage === null && placements.length === 0 ? (
        <EmptyState title={t('placements.empty')} />
      ) : null}

      {placements.length > 0 ? (
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
                <td>{placement.publicKey}</td>
                <td>{placement.status}</td>
                <td>{placement.defaultCreativeId ?? '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : null}
    </Stack>
  )
}
