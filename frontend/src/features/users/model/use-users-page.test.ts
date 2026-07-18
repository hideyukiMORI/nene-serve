import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useUsersPage } from './use-users-page'

describe('useUsersPage', () => {
  it('loads the user list and clears the loading state', async () => {
    const { result } = renderHookWithProviders(() => useUsersPage())

    expect(result.current.loading).toBe(true)

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })
    expect(result.current.errorKey).toBeNull()
    expect(result.current.users.length).toBeGreaterThan(0)
  })

  it('invite() resolves true and reports whether the invite email went out', async () => {
    const { result } = renderHookWithProviders(() => useUsersPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    let ok = false
    await act(async () => {
      ok = await result.current.invite('newcomer@acme.test', 'analyst')
    })

    expect(ok).toBe(true)
    expect(result.current.inviteErrorKey).toBeNull()
    await waitFor(() => {
      expect(result.current.lastInviteEmailSent).toBe(true)
    })
  })

  it('invite() resolves false and maps a conflicting email to an error key', async () => {
    mswServer.use(
      http.post('/admin/users', () =>
        HttpResponse.json(
          {
            type: 'https://nene-serve.dev/problems/user-exists',
            title: 'User already exists',
            status: 409,
            instance: '/admin/users',
          },
          { status: 409 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useUsersPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    let ok = true
    await act(async () => {
      ok = await result.current.invite('admin@acme.test', 'org_admin')
    })

    expect(ok).toBe(false)
    await waitFor(() => {
      expect(result.current.inviteErrorKey).toBe('common.error.conflict')
    })
  })
})
