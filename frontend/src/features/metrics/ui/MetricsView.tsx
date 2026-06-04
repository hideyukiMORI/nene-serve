import type { MetricsReport } from '@/entities/metrics'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { EmptyState, Stack, Text } from '@/shared/ui'

export interface MetricsViewProps {
  report: MetricsReport | null
  loading: boolean
  errorMessage: string | null
}

function formatInt(value: number): string {
  return new Intl.NumberFormat().format(value)
}

function formatPercent(ratio: number): string {
  return `${(ratio * 100).toFixed(2)}%`
}

export function MetricsView({ report, loading, errorMessage }: MetricsViewProps) {
  const { t } = useTranslation()

  const kpis: { labelKey: MessageKey; value: string }[] =
    report !== null
      ? [
          { labelKey: 'metrics.kpi.impressions', value: formatInt(report.totals.impressions) },
          { labelKey: 'metrics.kpi.clicks', value: formatInt(report.totals.clicks) },
          { labelKey: 'metrics.kpi.ctr', value: formatPercent(report.totals.ctr) },
          { labelKey: 'metrics.kpi.fillRate', value: formatPercent(report.totals.fillRate) },
        ]
      : []

  return (
    <Stack gap="md">
      <Stack gap="xs">
        <Text as="h1" variant="heading-md">
          {t('metrics.title')}
        </Text>
        <Text muted>{t('metrics.subtitle')}</Text>
      </Stack>

      {loading ? <Text muted>{t('metrics.loading')}</Text> : null}
      {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}

      {report !== null ? (
        <>
          <div className="row wrap g3">
            {kpis.map((kpi) => (
              <div
                key={kpi.labelKey}
                className="card stack g1"
                style={{ minWidth: 160, flex: '1 1 160px' }}
              >
                <Text muted variant="caption">
                  {t(kpi.labelKey)}
                </Text>
                <Text as="span" variant="heading-md">
                  {kpi.value}
                </Text>
              </div>
            ))}
          </div>

          {report.daily.length === 0 ? (
            <EmptyState title={t('metrics.empty')} />
          ) : (
            <table className="table">
              <thead>
                <tr>
                  <th>{t('metrics.column.date')}</th>
                  <th>{t('metrics.column.impressions')}</th>
                  <th>{t('metrics.column.clicks')}</th>
                  <th>{t('metrics.column.ctr')}</th>
                </tr>
              </thead>
              <tbody>
                {report.daily.map((day) => (
                  <tr key={day.date}>
                    <td>{day.date}</td>
                    <td>{formatInt(day.impressions)}</td>
                    <td>{formatInt(day.clicks)}</td>
                    <td>{formatPercent(day.ctr)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </>
      ) : null}
    </Stack>
  )
}
