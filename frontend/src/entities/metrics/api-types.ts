/** Wire shapes for GET /admin/metrics (snake_case, aggregate; no visitor ids). */
export interface MetricRowDto {
  date: string
  placement_id: string
  creative_id: string | null
  impressions: number
  clicks: number
  ctr: number
}

export interface FillRowDto {
  date: string
  placement_id: string
  serve_requests: number
  fills: number
  fill_rate: number
}

export interface MetricsReportDto {
  from: string
  to: string
  rows: MetricRowDto[]
  fill: FillRowDto[]
  conversions: unknown[]
}
