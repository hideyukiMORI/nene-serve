import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { MetricsReportDto } from './api-types'
import { mapMetricsReportDtoToModel } from './mapper'
import type { MetricsReport } from './model'
import { metricsKeys } from './query-keys'

/** Aggregate metrics for the default window (server defaults to the last 30 days). */
export function useMetricsReport(): UseQueryResult<MetricsReport, AppError> {
  return useQuery({
    queryKey: metricsKeys.report(),
    queryFn: async () => {
      const dto = await apiClient.get<MetricsReportDto>('/admin/metrics')
      return mapMetricsReportDtoToModel(dto)
    },
  })
}
