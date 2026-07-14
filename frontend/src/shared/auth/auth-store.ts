import { createSessionTokenStore } from '@hideyukimori/nene2-client'

type Listener = () => void

/**
 * Fleet-standard token store (nene2-js #102 / v1.1.0): `sessionStorage` in
 * browsers, in-memory elsewhere (tests). Storage failures fail closed (reads
 * behave as signed out). Never `localStorage`.
 *
 * Behavior change from the previous hand-written in-memory store: the token
 * now survives a page reload within the same browser tab (sessionStorage is
 * only cleared when the tab/browser closes), instead of being lost on every
 * reload. See migrate-product-client.md and this change's PR description.
 */
const sessionStore = createSessionTokenStore({ key: 'nene_serve_token' })

/**
 * Public surface preserved verbatim for existing callers (`Toggles.tsx`, the
 * login feature, `use-auth-token.ts`, the api client adapter) — only the
 * backing storage changed, from a module-scoped variable to the fleet
 * `SessionTokenStore`.
 */
export const authStore = {
  getToken(): string | null {
    return sessionStore.getToken()
  },
  setToken(next: string | null): void {
    if (next === null) {
      sessionStore.clearToken()
      return
    }
    sessionStore.setToken(next)
  },
  clear(): void {
    sessionStore.clearToken()
  },
  subscribe(listener: Listener): () => void {
    return sessionStore.subscribe(listener)
  },
}
