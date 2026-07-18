import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useCreativesPage } from './use-creatives-page'

describe('useCreativesPage', () => {
  it('loads the creative library and clears the loading state', async () => {
    const { result } = renderHookWithProviders(() => useCreativesPage())

    expect(result.current.loading).toBe(true)

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })
    expect(result.current.errorKey).toBeNull()
    expect(result.current.creatives.length).toBeGreaterThan(0)
  })

  it('maps a failing fetch to a localized error key', async () => {
    mswServer.use(
      http.get('/admin/creatives', () =>
        HttpResponse.json(
          { type: 'about:blank', title: 'Down', status: 500, instance: '/admin/creatives' },
          { status: 500 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useCreativesPage())

    await waitFor(() => {
      expect(result.current.errorKey).toBe('common.error.serverError')
    })
    expect(result.current.creatives).toEqual([])
  })
})
