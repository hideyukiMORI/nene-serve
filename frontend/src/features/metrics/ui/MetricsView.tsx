import type { MetricsReport } from '@/entities/metrics'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { EmptyState, Page, Stack, Text } from '@/shared/ui'

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
    <Page title={t('metrics.title')} subtitle={t('metrics.subtitle')} wide>
      <Stack gap="md">
        {loading ? <Text muted>{t('metrics.loading')}</Text> : null}
        {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}

        {report !== null ? (
          <>
            <div className="row wrap g3">
              {kpis.map((kpi) => (
                <div
                  key={kpi.labelKey}
                  className="card"
                  style={{ minWidth: 180, flex: '1 1 180px' }}
                >
                  <div className="kpi">
                    <span className="kpi-label">{t(kpi.labelKey)}</span>
                    <span className="kpi-val">{kpi.value}</span>
                  </div>
                </div>
              ))}
            </div>

            {report.daily.length === 0 ? (
              <EmptyState title={t('metrics.empty')} />
            ) : (
              <div className="table-wrap">
                <table className="table">
                  <thead>
                    <tr>
                      <th>{t('metrics.column.date')}</th>
                      <th className="num">{t('metrics.column.impressions')}</th>
                      <th className="num">{t('metrics.column.clicks')}</th>
                      <th className="num">{t('metrics.column.ctr')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {report.daily.map((day) => (
                      <tr key={day.date}>
                        <td className="mono">{day.date}</td>
                        <td className="num">{formatInt(day.impressions)}</td>
                        <td className="num">{formatInt(day.clicks)}</td>
                        <td className="num">{formatPercent(day.ctr)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </>
        ) : null}
      </Stack>
    </Page>
  )
}
