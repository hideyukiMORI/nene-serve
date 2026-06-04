import { describe, expect, it } from 'vitest'
import { mapMetricsReportDtoToModel } from './mapper'

describe('metrics mapper', () => {
  it('aggregates rows per day and computes totals', () => {
    const report = mapMetricsReportDtoToModel({
      from: '2026-01-01',
      to: '2026-01-02',
      rows: [
        {
          date: '2026-01-01',
          placement_id: 'p1',
          creative_id: 'c1',
          impressions: 100,
          clicks: 10,
          ctr: 0.1,
        },
        {
          date: '2026-01-01',
          placement_id: 'p2',
          creative_id: 'c2',
          impressions: 100,
          clicks: 30,
          ctr: 0.3,
        },
        {
          date: '2026-01-02',
          placement_id: 'p1',
          creative_id: 'c1',
          impressions: 200,
          clicks: 20,
          ctr: 0.1,
        },
      ],
      fill: [
        { date: '2026-01-01', placement_id: 'p1', serve_requests: 500, fills: 400, fill_rate: 0.8 },
      ],
      conversions: [],
    })

    expect(report.daily).toHaveLength(2)
    expect(report.daily[0]).toEqual({ date: '2026-01-01', impressions: 200, clicks: 40, ctr: 0.2 })
    expect(report.totals.impressions).toBe(400)
    expect(report.totals.clicks).toBe(60)
    expect(report.totals.ctr).toBeCloseTo(0.15, 5)
    expect(report.totals.fillRate).toBeCloseTo(0.8, 5)
  })

  it('treats a zero denominator as a zero rate', () => {
    const report = mapMetricsReportDtoToModel({
      from: '2026-01-01',
      to: '2026-01-01',
      rows: [],
      fill: [],
      conversions: [],
    })
    expect(report.totals.ctr).toBe(0)
    expect(report.totals.fillRate).toBe(0)
  })
})
