import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useMarketplacePage } from './use-marketplace-page'

describe('useMarketplacePage', () => {
  it('loads advertisers, pricing rules and campaigns together', async () => {
    const { result } = renderHookWithProviders(() => useMarketplacePage())

    expect(result.current.loading).toBe(true)

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })
    expect(result.current.errorKey).toBeNull()
    expect(result.current.advertisers.length).toBeGreaterThan(0)
    expect(result.current.pricingRules.length).toBeGreaterThan(0)
    expect(result.current.campaigns.length).toBeGreaterThan(0)
  })

  it('surfaces an error key when any one of the three sources fails', async () => {
    mswServer.use(
      http.get('/admin/pricing-rules', () =>
        HttpResponse.json(
          { type: 'about:blank', title: 'Down', status: 500, instance: '/admin/pricing-rules' },
          { status: 500 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useMarketplacePage())

    await waitFor(() => {
      expect(result.current.errorKey).toBe('common.error.serverError')
    })
    expect(result.current.pricingRules).toEqual([])
  })
})
