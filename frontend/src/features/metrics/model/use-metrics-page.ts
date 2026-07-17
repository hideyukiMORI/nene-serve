import { useMetricsReport, type MetricsReport } from '@/entities/metrics'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface MetricsPage {
  report: MetricsReport | null
  loading: boolean
  errorKey: MessageKey | null
}

export function useMetricsPage(): MetricsPage {
  const query = useMetricsReport()
  return {
    report: query.data ?? null,
    loading: query.isPending,
    errorKey: query.error !== null ? mapProblemDetailsToMessageKey(query.error) : null,
  }
}
