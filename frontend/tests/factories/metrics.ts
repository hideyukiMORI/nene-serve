import type { MetricsReportDto } from '@/entities/metrics/api-types'

export function makeMetricsReportDto(overrides: Partial<MetricsReportDto> = {}): MetricsReportDto {
  return {
    from: '2026-05-06',
    to: '2026-06-04',
    rows: [
      {
        date: '2026-06-03',
        placement_id: 'plc-acme-home',
        creative_id: 'cr-acme-banner',
        impressions: 1000,
        clicks: 50,
        ctr: 0.05,
      },
      {
        date: '2026-06-04',
        placement_id: 'plc-acme-home',
        creative_id: 'cr-acme-banner',
        impressions: 2000,
        clicks: 80,
        ctr: 0.04,
      },
    ],
    fill: [
      {
        date: '2026-06-03',
        placement_id: 'plc-acme-home',
        serve_requests: 1200,
        fills: 1000,
        fill_rate: 0.8333,
      },
      {
        date: '2026-06-04',
        placement_id: 'plc-acme-home',
        serve_requests: 2400,
        fills: 2000,
        fill_rate: 0.8333,
      },
    ],
    conversions: [],
    ...overrides,
  }
}
