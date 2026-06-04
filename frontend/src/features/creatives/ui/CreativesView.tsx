import type { Creative } from '@/entities/creative'
import { useTranslation } from '@/shared/i18n'
import { EmptyState, Page, Pill, reviewStatusTone, Stack, Text } from '@/shared/ui'
import { CreateImageCreativeForm } from './CreateImageCreativeForm'

export interface CreativesViewProps {
  creatives: Creative[]
  loading: boolean
  errorMessage: string | null
}

export function CreativesView({ creatives, loading, errorMessage }: CreativesViewProps) {
  const { t } = useTranslation()

  return (
    <Page title={t('creatives.title')} subtitle={t('creatives.subtitle')}>
      <Stack gap="md">
        <CreateImageCreativeForm />

        {loading ? <Text muted>{t('creatives.loading')}</Text> : null}
        {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}

        {!loading && errorMessage === null && creatives.length === 0 ? (
          <EmptyState title={t('creatives.empty')} />
        ) : null}

        {creatives.length > 0 ? (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>{t('creatives.column.id')}</th>
                  <th>{t('creatives.column.type')}</th>
                  <th>{t('creatives.column.status')}</th>
                  <th className="num">{t('creatives.column.version')}</th>
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
                    <td className="num">{creative.version}</td>
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
