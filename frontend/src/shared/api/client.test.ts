import { http, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mswServer } from '@tests/msw/server'

/**
 * Product-level assertion that the `@hideyukimori/nene2-client` transport
 * adopted in this client (nene-serve issue #152) still carries this app's
 * required headers on a representative authenticated request: the fleet
 * `Authorization` / `X-Authorization` mirror (nene2-js #102), plus this
 * product's own `X-Organization-Slug` and `X-NENE2-API-Key` (previously
 * hand-built in `client.ts`, now passed through the transport's own
 * `headers` / `apiKey` config).
 *
 * `env` (and therefore the transport singleton in `client.ts`) is read once
 * at module-evaluation time, so the org slug / API key are stubbed via
 * `vi.stubEnv` *before* a fresh dynamic import of both `@/shared/auth` and
 * `@/shared/api/client` (via `vi.resetModules`) rather than mutating the
 * already-evaluated default instance.
 */
describe('apiClient header adapter (nene2-client transport)', () => {
  beforeEach(() => {
    vi.stubEnv('VITE_ORG_SLUG', 'acme')
    vi.stubEnv('VITE_API_KEY', 'test-machine-api-key')
    vi.resetModules()
  })

  afterEach(() => {
    vi.unstubAllEnvs()
    mswServer.resetHandlers()
  })

  it('attaches Authorization, X-Authorization, X-Organization-Slug and X-NENE2-API-Key', async () => {
    let seenHeaders: Headers | undefined
    mswServer.use(
      http.get('/admin/me', ({ request }) => {
        seenHeaders = request.headers
        return HttpResponse.json({ id: 'user-acme-admin' })
      }),
    )

    const { authStore } = await import('@/shared/auth')
    const { apiClient } = await import('@/shared/api/client')
    authStore.setToken('header.payload.signature')

    await apiClient.get('/admin/me')

    expect(seenHeaders?.get('Authorization')).toBe('Bearer header.payload.signature')
    expect(seenHeaders?.get('X-Authorization')).toBe('Bearer header.payload.signature')
    expect(seenHeaders?.get('X-Organization-Slug')).toBe('acme')
    expect(seenHeaders?.get('X-NENE2-API-Key')).toBe('test-machine-api-key')

    authStore.clear()
  })
})
