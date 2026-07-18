import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useReviewPage } from './use-review-page'

describe('useReviewPage', () => {
  it('loads the review queue and clears the loading state', async () => {
    const { result } = renderHookWithProviders(() => useReviewPage())

    expect(result.current.loading).toBe(true)

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })
    expect(result.current.errorKey).toBeNull()
    // Fleet assertion standard: count, contract property, and one boundary
    // anchor — not a transcription of the fixture ids.
    expect(result.current.creatives.length).toBeGreaterThan(0)
    expect(
      result.current.creatives.every(
        (c) => c.reviewStatus === 'submitted' || c.reviewStatus === 'in_review',
      ),
    ).toBe(true)
    expect(result.current.creatives[0]?.id).toBe('cr-acme-pending')
  })

  it('maps a failing queue fetch to a localized error key', async () => {
    mswServer.use(
      http.get('/admin/review-queue', () =>
        HttpResponse.json(
          { type: 'about:blank', title: 'Down', status: 503, instance: '/admin/review-queue' },
          { status: 503 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useReviewPage())

    await waitFor(() => {
      expect(result.current.errorKey).toBe('common.error.serverError')
    })
    expect(result.current.creatives).toEqual([])
  })

  it('act() resolves true when the review transition succeeds', async () => {
    const { result } = renderHookWithProviders(() => useReviewPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    let ok = false
    await act(async () => {
      ok = await result.current.act('cr-acme-pending', 'approve')
    })

    expect(ok).toBe(true)
    expect(result.current.actionErrorKey).toBeNull()
  })

  it('act() resolves false and exposes the error key when the transition is forbidden', async () => {
    mswServer.use(
      http.post('/admin/creatives/:id/:action', () =>
        HttpResponse.json(
          {
            type: 'https://nene-serve.dev/problems/four-eyes',
            title: 'Second approver required',
            status: 403,
            instance: '/admin/creatives/cr-acme-pending/approve',
          },
          { status: 403 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useReviewPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    let ok = true
    await act(async () => {
      ok = await result.current.act('cr-acme-pending', 'approve')
    })

    expect(ok).toBe(false)
    await waitFor(() => {
      expect(result.current.actionErrorKey).toBe('common.error.forbidden')
    })
  })
})
