import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useSettingsPage } from './use-settings-page'

describe('useSettingsPage', () => {
  it('loads the smtp settings and clears the loading state', async () => {
    const { result } = renderHookWithProviders(() => useSettingsPage())

    expect(result.current.loading).toBe(true)

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })
    expect(result.current.errorKey).toBeNull()
    expect(result.current.settings).not.toBeNull()
    expect(result.current.settings?.host.length).toBeGreaterThan(0)
  })

  it('save() resolves true and marks the page saved', async () => {
    const { result } = renderHookWithProviders(() => useSettingsPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    let ok = false
    await act(async () => {
      ok = await result.current.save({
        host: 'smtp.acme.test',
        port: 587,
        username: 'mailer',
        password: 's3cret',
        fromAddress: 'noreply@acme.test',
        fromName: 'Acme',
        encryption: 'tls',
      })
    })

    expect(ok).toBe(true)
    await waitFor(() => {
      expect(result.current.saved).toBe(true)
    })
    expect(result.current.saveErrorKey).toBeNull()
  })

  it('save() resolves false and maps the rejection to an error key', async () => {
    mswServer.use(
      http.put('/admin/settings/smtp', () =>
        HttpResponse.json(
          {
            type: 'https://nene-serve.dev/problems/smtp-invalid',
            title: 'Invalid SMTP settings',
            status: 422,
            instance: '/admin/settings/smtp',
          },
          { status: 422 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useSettingsPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    let ok = true
    await act(async () => {
      ok = await result.current.save({
        host: '',
        port: 0,
        username: '',
        password: '',
        fromAddress: '',
        fromName: '',
        encryption: 'none',
      })
    })

    expect(ok).toBe(false)
    await waitFor(() => {
      expect(result.current.saveErrorKey).toBe('common.error.validation')
    })
  })

  it('test() reports ok when the test mail is sent', async () => {
    const { result } = renderHookWithProviders(() => useSettingsPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    await act(async () => {
      await result.current.test()
    })

    await waitFor(() => {
      expect(result.current.testResultKey).toBe('ok')
    })
  })

  it('test() maps a delivery failure to an error key instead of throwing', async () => {
    mswServer.use(
      http.post('/admin/settings/smtp/test', () =>
        HttpResponse.json(
          {
            type: 'https://nene-serve.dev/problems/smtp-unreachable',
            title: 'SMTP unreachable',
            status: 502,
            instance: '/admin/settings/smtp/test',
          },
          { status: 502 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useSettingsPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    await act(async () => {
      await result.current.test()
    })

    await waitFor(() => {
      expect(result.current.testResultKey).toBe('common.error.serverError')
    })
  })
})
