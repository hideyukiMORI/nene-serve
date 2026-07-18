import { act } from 'react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { AppError } from '@/shared/api/client'
import { useAcceptInvitation } from './mutations'

describe('useAcceptInvitation', () => {
  it('posts the token and password and returns the accepted flag', async () => {
    let captured: unknown
    mswServer.use(
      http.post('/admin/invitations/accept', async ({ request }) => {
        captured = await request.json()
        return HttpResponse.json({ accepted: true })
      }),
    )

    const { result } = renderHookWithProviders(() => useAcceptInvitation())

    await act(async () => {
      const outcome = await result.current.mutateAsync({
        token: 'good-token',
        password: 'correct horse battery staple',
      })
      expect(outcome).toEqual({ accepted: true })
    })

    expect(captured).toEqual({
      token: 'good-token',
      password: 'correct horse battery staple',
    })
  })

  it('surfaces an invalid-token rejection as a typed AppError', async () => {
    mswServer.use(
      http.post('/admin/invitations/accept', () =>
        HttpResponse.json(
          {
            type: 'https://nene-serve.dev/problems/invitation-invalid',
            title: 'Invalid invitation',
            status: 404,
            instance: '/admin/invitations/accept',
          },
          { status: 404 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useAcceptInvitation())

    await act(async () => {
      await expect(
        result.current.mutateAsync({ token: 'expired', password: 'irrelevant' }),
      ).rejects.toSatisfy((error: unknown) => {
        expect(error).toBeInstanceOf(AppError)
        expect((error as AppError).status).toBe(404)
        return true
      })
    })
  })
})
