import {
  createNene2Transport,
  isNene2ClientError,
  validationErrorsFromClientError,
  type TokenStore,
} from '@hideyukimori/nene2-client'
import { env } from '@/shared/config/env'
import { authStore } from '@/shared/auth'
import { AppError } from '@/shared/api/errors'

// The transport's TokenStore contract is `getToken`/`clearToken`; auth-store's
// public surface predates the fleet client and keeps `clear()` (used directly
// by Toggles.tsx and tests). Adapt here rather than rename either surface.
const tokenStore: TokenStore = {
  getToken: () => authStore.getToken(),
  clearToken: () => {
    authStore.clear()
  },
}

// This product's two custom headers, carried through the transport's own
// static-header/apiKey config (the documented nene-deal mapping) rather than
// a hand-rolled fetch wrapper — both still flow through the transport's single
// auth-header choke point, which applies auth headers last so neither of
// these (nor any per-request header) can override or drop the mirror.
// Only set when configured, matching the previous hand-written client: an
// empty string would otherwise send the header with an empty value instead of
// omitting it, which the backend could observe as a difference.
const staticHeaders: Record<string, string> = {}
if (env.orgSlug !== '') {
  staticHeaders['X-Organization-Slug'] = env.orgSlug
}

const transport = createNene2Transport({
  baseUrl: env.apiBaseUrl,
  tokenStore,
  apiKey: env.apiKey !== '' ? env.apiKey : undefined,
  headers: staticHeaders,
  credentials: 'include',
  // The transport is a module-level singleton, so it resolves `fetch` once at
  // import time and binds that reference for its lifetime. In tests, msw's
  // `setupServer().listen()` (called from `beforeAll`, i.e. after this module
  // has already been imported) reassigns `globalThis.fetch` to its
  // interceptor-aware version; a reference captured earlier would silently
  // bypass msw and hit the pre-patch fetch (which fails to resolve relative
  // URLs outside a browser). Looking `globalThis.fetch` up lazily on every
  // call reproduces the old client's behavior (a bare `fetch(...)` call,
  // which is always a fresh global lookup) and keeps it msw-compatible.
  fetch: (input, init) => globalThis.fetch(input, init),
  // The transport clears the token store itself on 401 of an authenticated
  // request (clearTokenOnStatuses defaults to [401]) and only fires this hook
  // when a token was actually attached — never for an anonymous /admin/login
  // attempt. That reproduces the previous path-based
  // `!path.includes('/auth/login')` exemption (a login attempt carries no
  // bearer token, so `tokenAttached` is false either way). The auth gate
  // re-renders from `authStore.subscribe`, so no extra side effect is needed
  // here (matches the previous behavior, which had none either).
  onUnauthorized: () => {},
})

/**
 * Map a transport failure onto the app's existing {@link AppError} shape so
 * every call site (`instanceof AppError`, `.status`, `.isRetryable`,
 * `.errors`) keeps working unchanged. Domain error mapping stays app-side by
 * design (see migrate-product-client.md).
 */
function toAppError(error: unknown): AppError {
  if (!isNene2ClientError(error)) {
    throw error
  }
  const problem = error.problem
  if (problem === undefined) {
    return new AppError({
      type: 'about:blank',
      title: error.message || 'Request failed',
      status: error.status,
      instance: error.url,
    })
  }
  const errors = validationErrorsFromClientError(error)
  return new AppError({
    type: problem.type,
    title: problem.title,
    status: problem.status,
    // The package's ProblemDetails marks `instance` optional (not every NENE2
    // backend sends it); this app's contract always does, so fall back to the
    // request URL — matching the old client's parse-failure fallback.
    instance: problem.instance ?? error.url,
    ...(problem.detail !== undefined && { detail: problem.detail }),
    ...(errors !== undefined && { errors }),
  })
}

async function unwrap<T>(promise: Promise<T>): Promise<T> {
  try {
    return await promise
  } catch (error) {
    throw toAppError(error)
  }
}

export const apiClient = {
  get<T>(path: string, signal?: AbortSignal): Promise<T> {
    return unwrap(transport.get<T>(path, signal !== undefined ? { signal } : undefined))
  },
  post<T>(path: string, body: unknown): Promise<T> {
    return unwrap(transport.post<T>(path, body))
  },
  put<T>(path: string, body: unknown): Promise<T> {
    return unwrap(transport.put<T>(path, body))
  },
  patch<T>(path: string, body: unknown): Promise<T> {
    return unwrap(transport.patch<T>(path, body))
  },
  delete(path: string): Promise<undefined> {
    return unwrap(transport.delete<undefined>(path))
  },
}

export { AppError }
