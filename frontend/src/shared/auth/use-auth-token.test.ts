import { act, renderHook } from '@testing-library/react'
import { afterEach, describe, expect, it } from 'vitest'
import { authStore } from './auth-store'
import { useAuthToken } from './use-auth-token'

describe('useAuthToken', () => {
  afterEach(() => {
    authStore.clear()
  })

  it('returns null when signed out', () => {
    const { result } = renderHook(() => useAuthToken())
    expect(result.current).toBeNull()
  })

  it('re-renders with the token when it is stored', () => {
    const { result } = renderHook(() => useAuthToken())

    act(() => {
      authStore.setToken('header.payload.signature')
    })

    expect(result.current).toBe('header.payload.signature')
  })

  it('re-renders with null when the token is cleared', () => {
    authStore.setToken('header.payload.signature')
    const { result } = renderHook(() => useAuthToken())
    expect(result.current).toBe('header.payload.signature')

    act(() => {
      authStore.clear()
    })

    expect(result.current).toBeNull()
  })
})
