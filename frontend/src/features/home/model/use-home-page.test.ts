import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useHomePage } from './use-home-page'

describe('useHomePage', () => {
  it('marks every onboarding step done for a fully set-up account', async () => {
    const { result } = renderHookWithProviders(() => useHomePage())

    await waitFor(() => {
      expect(result.current.done.size).toBe(7)
    })
    expect([...result.current.done].sort()).toEqual([
      'approve',
      'creative',
      'embed',
      'invite',
      'measure',
      'placement',
      'smtp',
    ])
  })

  it('derives an empty step set for a brand-new account', async () => {
    mswServer.use(
      http.get('/admin/settings/smtp', () =>
        HttpResponse.json({
          host: '',
          port: 0,
          username: '',
          from_address: '',
          from_name: '',
          encryption: 'none',
          has_password: false,
          configured: false,
        }),
      ),
      http.get('/admin/users', () =>
        HttpResponse.json({
          items: [
            { id: 'u-1', organization_id: 'org-1', email: 'owner@new.test', role: 'org_admin' },
          ],
          limit: 1,
          offset: 0,
        }),
      ),
      http.get('/admin/placements', () => HttpResponse.json({ items: [], limit: 0, offset: 0 })),
      http.get('/admin/creatives', () => HttpResponse.json({ items: [], limit: 0, offset: 0 })),
      http.get('/admin/metrics', () => HttpResponse.json({ rows: [], fill: [] })),
    )

    const { result } = renderHookWithProviders(() => useHomePage())

    // `done` starts empty, so settle all five queries before asserting that
    // none of them added a step.
    await act(async () => {
      await new Promise((resolve) => setTimeout(resolve, 150))
    })
    expect(result.current.done.size).toBe(0)
  })
})
