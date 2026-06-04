import type { Creative } from '@/entities/creative'
import { useTranslation } from '@/shared/i18n'
import { EmptyState, Stack, Text } from '@/shared/ui'

export interface CreativesViewProps {
  creatives: Creative[]
  loading: boolean
  errorMessage: string | null
}

export function CreativesView({ creatives, loading, errorMessage }: CreativesViewProps) {
  const { t } = useTranslation()

  return (
    <Stack gap="md">
      <Stack gap="xs">
        <Text as="h1" variant="heading-md">
          {t('creatives.title')}
        </Text>
        <Text muted>{t('creatives.subtitle')}</Text>
      </Stack>

      {loading ? <Text muted>{t('creatives.loading')}</Text> : null}
      {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}

      {!loading && errorMessage === null && creatives.length === 0 ? (
        <EmptyState title={t('creatives.empty')} />
      ) : null}

      {creatives.length > 0 ? (
        <table className="table">
          <thead>
            <tr>
              <th>{t('creatives.column.id')}</th>
              <th>{t('creatives.column.type')}</th>
              <th>{t('creatives.column.status')}</th>
              <th>{t('creatives.column.version')}</th>
            </tr>
          </thead>
          <tbody>
            {creatives.map((creative) => (
              <tr key={creative.id}>
                <td>{creative.id}</td>
                <td>{creative.type}</td>
                <td>{creative.reviewStatus}</td>
                <td>{creative.version}</td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : null}
    </Stack>
  )
}
