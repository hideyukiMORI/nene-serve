import type { MetricsReportDto } from './api-types'
import type { DailyMetric, MetricsReport } from './model'

function ratio(numerator: number, denominator: number): number {
  return denominator === 0 ? 0 : numerator / denominator
}

/** Aggregate the per-placement rows into per-day totals + overall totals. */
export function mapMetricsReportDtoToModel(dto: MetricsReportDto): MetricsReport {
  const byDate = new Map<string, { impressions: number; clicks: number }>()
  for (const row of dto.rows) {
    const acc = byDate.get(row.date) ?? { impressions: 0, clicks: 0 }
    acc.impressions += row.impressions
    acc.clicks += row.clicks
    byDate.set(row.date, acc)
  }

  const daily: DailyMetric[] = [...byDate.entries()]
    .map(([date, v]) => ({
      date,
      impressions: v.impressions,
      clicks: v.clicks,
      ctr: ratio(v.clicks, v.impressions),
    }))
    .sort((a, b) => a.date.localeCompare(b.date))

  const impressions = dto.rows.reduce((sum, r) => sum + r.impressions, 0)
  const clicks = dto.rows.reduce((sum, r) => sum + r.clicks, 0)
  const serveRequests = dto.fill.reduce((sum, f) => sum + f.serve_requests, 0)
  const fills = dto.fill.reduce((sum, f) => sum + f.fills, 0)

  return {
    from: dto.from,
    to: dto.to,
    daily,
    totals: {
      impressions,
      clicks,
      ctr: ratio(clicks, impressions),
      serveRequests,
      fills,
      fillRate: ratio(fills, serveRequests),
    },
  }
}
