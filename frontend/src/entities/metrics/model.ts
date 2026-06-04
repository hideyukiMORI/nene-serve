/** UI model for the metrics dashboard (camelCase, aggregated). */
export interface DailyMetric {
  date: string
  impressions: number
  clicks: number
  ctr: number
}

export interface MetricsTotals {
  impressions: number
  clicks: number
  ctr: number
  serveRequests: number
  fills: number
  fillRate: number
}

export interface MetricsReport {
  from: string
  to: string
  daily: DailyMetric[]
  totals: MetricsTotals
}
