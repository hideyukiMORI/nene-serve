import { http, HttpResponse } from 'msw'
import type { MetricsReportDto } from '@/entities/metrics/api-types'

interface Series {
  placementId: string
  creativeId: string
  base: number
  ctr: number
}

const SERIES: Series[] = [
  { placementId: 'plc-acme-home', creativeId: 'cr-acme-banner', base: 5200, ctr: 0.045 },
  { placementId: 'plc-acme-footer', creativeId: 'cr-acme-leaderboard', base: 2300, ctr: 0.034 },
  { placementId: 'plc-acme-side', creativeId: 'cr-acme-square', base: 1200, ctr: 0.027 },
]

const DAYS = 21
const END = Date.UTC(2026, 5, 4) // 2026-06-04, last day in the window
const DAY_MS = 86_400_000

function isoDay(offsetFromEnd: number): string {
  return new Date(END - offsetFromEnd * DAY_MS).toISOString().slice(0, 10)
}

/**
 * Deterministic operational traffic: a gentle weekly wave with lighter
 * weekends, across three placements over the last 21 days. No randomness so
 * the numbers are stable between reloads.
 */
function buildReport(): MetricsReportDto {
  const rows: MetricsReportDto['rows'] = []
  const fill: MetricsReportDto['fill'] = []

  for (let offset = DAYS - 1; offset >= 0; offset--) {
    const date = isoDay(offset)
    const dow = new Date(END - offset * DAY_MS).getUTCDay()
    const weekend = dow === 0 || dow === 6 ? 0.68 : 1
    const wave = 1 + 0.18 * Math.sin(offset / 2.5)

    for (const series of SERIES) {
      const impressions = Math.round(series.base * weekend * wave)
      const clicks = Math.round(impressions * series.ctr * (0.85 + 0.3 * ((offset % 5) / 5)))
      rows.push({
        date,
        placement_id: series.placementId,
        creative_id: series.creativeId,
        impressions,
        clicks,
        ctr: impressions === 0 ? 0 : clicks / impressions,
      })

      const serveRequests = Math.round(impressions / (0.82 + 0.12 * ((offset % 4) / 4)))
      fill.push({
        date,
        placement_id: series.placementId,
        serve_requests: serveRequests,
        fills: impressions,
        fill_rate: serveRequests === 0 ? 0 : impressions / serveRequests,
      })
    }
  }

  return { from: isoDay(DAYS - 1), to: isoDay(0), rows, fill, conversions: [] }
}

export const metricsHandlers = [http.get('/admin/metrics', () => HttpResponse.json(buildReport()))]
