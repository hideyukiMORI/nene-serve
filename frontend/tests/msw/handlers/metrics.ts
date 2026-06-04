import { http, HttpResponse } from 'msw'
import { makeMetricsReportDto } from '@tests/factories/metrics'

export const metricsHandlers = [
  http.get('/admin/metrics', () => HttpResponse.json(makeMetricsReportDto())),
]
