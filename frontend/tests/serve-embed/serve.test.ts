import { readFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { waitFor } from '@testing-library/dom'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

// Execute the real shipped artifact (public_html/serve.js) in jsdom.
const here = dirname(fileURLToPath(import.meta.url))
const serveSource = readFileSync(resolve(here, '../../../public_html/serve.js'), 'utf8')

interface FakeResponse {
  status: number
  ok: boolean
  json: () => Promise<unknown>
}

function runServeJs(): void {
  // eslint-disable-next-line @typescript-eslint/no-implied-eval
  new Function(serveSource)()
}

describe('serve.js embed client', () => {
  let calls: { url: string; method: string }[]

  beforeEach(() => {
    calls = []
    const fetchMock = vi.fn((url: string, opts?: { method?: string }): Promise<FakeResponse> => {
      calls.push({ url, method: opts?.method ?? 'GET' })
      if (url.includes('/public/placements/')) {
        return Promise.resolve({
          status: 200,
          ok: true,
          json: () =>
            Promise.resolve({
              creative: {
                type: 'image',
                asset_url: 'https://cdn.acme.test/banner.png',
                width: 300,
                height: 250,
              },
              impression_token: 'imp_test',
              click_url: '/public/clicks/ck_test',
            }),
        })
      }
      return Promise.resolve({ status: 204, ok: true, json: () => Promise.resolve(null) })
    })
    vi.stubGlobal('fetch', fetchMock)
    document.body.innerHTML =
      '<script data-placement="pk_test" src="https://serve.example/serve.js"></script>'
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    document.body.innerHTML = ''
  })

  it('serves, renders the image wrapped in a click anchor, and beacons the impression', async () => {
    runServeJs()

    const img = await waitFor(() => {
      const found = document.querySelector('img')
      if (found === null) throw new Error('no img yet')
      return found
    })

    expect(img.getAttribute('src')).toBe('https://cdn.acme.test/banner.png')

    // Click target is wrapped through the opaque click token (no open redirect).
    const anchor = img.closest('a')
    expect(anchor?.getAttribute('href')).toBe('https://serve.example/public/clicks/ck_test')
    expect(anchor?.getAttribute('rel')).toContain('noopener')

    // The serve request and the impression beacon both fired.
    expect(calls.some((c) => c.url.includes('/public/placements/pk_test/serve'))).toBe(true)
    await waitFor(() => {
      expect(
        calls.some((c) => c.url.includes('/public/events/impression') && c.method === 'POST'),
      ).toBe(true)
    })
  })

  it('renders nothing on an empty serve (204)', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn((): Promise<FakeResponse> =>
        Promise.resolve({ status: 204, ok: true, json: () => Promise.resolve(null) }),
      ),
    )
    runServeJs()
    await new Promise((r) => setTimeout(r, 0))
    expect(document.querySelector('img')).toBeNull()
    expect(document.querySelector('iframe')).toBeNull()
  })
})
