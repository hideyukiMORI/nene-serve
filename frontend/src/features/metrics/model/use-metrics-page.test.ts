import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useMetricsPage } from './use-metrics-page'

describe('useMetricsPage', () => {
  it('loads the aggregated report and clears the loading state', async () => {
    const { result } = renderHookWithProviders(() => useMetricsPage())

    expect(result.current.loading).toBe(true)

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })
    expect(result.current.errorKey).toBeNull()
    expect(result.current.report).not.toBeNull()
    expect(result.current.report?.totals.impressions).toBeGreaterThan(0)
    expect(result.current.report?.daily.length).toBeGreaterThan(0)
  })

  it('maps a failing fetch to a localized error key', async () => {
    mswServer.use(
      http.get('/admin/metrics', () =>
        HttpResponse.json(
          { type: 'about:blank', title: 'Down', status: 502, instance: '/admin/metrics' },
          { status: 502 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useMetricsPage())

    await waitFor(() => {
      expect(result.current.errorKey).toBe('common.error.serverError')
    })
    expect(result.current.report).toBeNull()
  })
})
