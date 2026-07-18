import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useSetPasswordPage } from './use-set-password-page'

describe('useSetPasswordPage', () => {
  it('previews a valid invitation and exposes the invitee email', async () => {
    const { result } = renderHookWithProviders(() => useSetPasswordPage('good-token'))

    expect(result.current.validating).toBe(true)

    await waitFor(() => {
      expect(result.current.validating).toBe(false)
    })
    expect(result.current.email).toBe('invitee@acme.test')
    expect(result.current.invalid).toBe(false)
  })

  it('marks an unknown token invalid after the preview rejects', async () => {
    const { result } = renderHookWithProviders(() => useSetPasswordPage('expired-token'))

    await waitFor(() => {
      expect(result.current.invalid).toBe(true)
    })
    expect(result.current.email).toBeNull()
  })

  it('treats an empty token as invalid without validating', () => {
    const { result } = renderHookWithProviders(() => useSetPasswordPage(''))

    expect(result.current.validating).toBe(false)
    expect(result.current.invalid).toBe(true)
  })

  it('submit() resolves true when the invitation is accepted', async () => {
    const { result } = renderHookWithProviders(() => useSetPasswordPage('good-token'))
    await waitFor(() => {
      expect(result.current.validating).toBe(false)
    })

    let ok = false
    await act(async () => {
      ok = await result.current.submit('correct horse battery staple')
    })

    expect(ok).toBe(true)
    expect(result.current.submitErrorKey).toBeNull()
  })

  it('submit() resolves false and maps the rejection to an error key', async () => {
    mswServer.use(
      http.post('/admin/invitations/accept', () =>
        HttpResponse.json(
          {
            type: 'https://nene-serve.dev/problems/weak-password',
            title: 'Password too weak',
            status: 422,
            instance: '/admin/invitations/accept',
          },
          { status: 422 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useSetPasswordPage('good-token'))
    await waitFor(() => {
      expect(result.current.validating).toBe(false)
    })

    let ok = true
    await act(async () => {
      ok = await result.current.submit('123')
    })

    expect(ok).toBe(false)
    await waitFor(() => {
      expect(result.current.submitErrorKey).toBe('common.error.validation')
    })
  })
})
