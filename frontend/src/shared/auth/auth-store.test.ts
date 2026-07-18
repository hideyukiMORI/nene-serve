import { afterEach, describe, expect, it, vi } from 'vitest'
import { authStore } from './auth-store'

describe('authStore', () => {
  afterEach(() => {
    authStore.clear()
    sessionStorage.clear()
  })

  it('reads as signed out before any token is stored', () => {
    expect(authStore.getToken()).toBeNull()
  })

  it('returns the stored token after setToken', () => {
    authStore.setToken('header.payload.signature')
    expect(authStore.getToken()).toBe('header.payload.signature')
  })

  it('persists to sessionStorage (reload-survival contract), never localStorage', () => {
    authStore.setToken('header.payload.signature')

    expect(sessionStorage.getItem('nene_serve_token')).not.toBeNull()
    expect(localStorage.length).toBe(0)
  })

  it('setToken(null) signs out and removes the persisted entry', () => {
    authStore.setToken('header.payload.signature')
    authStore.setToken(null)

    expect(authStore.getToken()).toBeNull()
    expect(sessionStorage.getItem('nene_serve_token')).toBeNull()
  })

  it('clear() signs out and removes the persisted entry', () => {
    authStore.setToken('header.payload.signature')
    authStore.clear()

    expect(authStore.getToken()).toBeNull()
    expect(sessionStorage.getItem('nene_serve_token')).toBeNull()
  })

  it('notifies subscribers on setToken and on clear', () => {
    const listener = vi.fn()
    const unsubscribe = authStore.subscribe(listener)

    authStore.setToken('header.payload.signature')
    expect(listener).toHaveBeenCalledTimes(1)

    authStore.clear()
    expect(listener).toHaveBeenCalledTimes(2)

    unsubscribe()
  })

  it('stops notifying after unsubscribe', () => {
    const listener = vi.fn()
    const unsubscribe = authStore.subscribe(listener)
    unsubscribe()

    authStore.setToken('header.payload.signature')
    expect(listener).not.toHaveBeenCalled()
  })
})
