import { act } from 'react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { AppError } from '@/shared/api/client'
import { useUploadAsset } from './mutations'

describe('useUploadAsset', () => {
  it('posts the snake_case wire body and returns the upload result', async () => {
    let captured: unknown
    mswServer.use(
      http.post('/admin/assets', async ({ request }) => {
        captured = await request.json()
        return HttpResponse.json(
          {
            id: 'ast-1',
            kind: 'image',
            content_type: 'image/png',
            byte_size: 10,
            asset_url: '/public/assets/ast-1',
          },
          { status: 201 },
        )
      }),
    )

    const { result } = renderHookWithProviders(() => useUploadAsset())

    await act(async () => {
      const uploaded = await result.current.mutateAsync({
        contentType: 'image/png',
        dataBase64: 'AAAA',
      })
      expect(uploaded.id).toBe('ast-1')
      expect(uploaded.asset_url).toBe('/public/assets/ast-1')
    })

    expect(captured).toEqual({ content_type: 'image/png', data_base64: 'AAAA' })
  })

  it('surfaces a problem+json rejection as a typed AppError', async () => {
    mswServer.use(
      http.post('/admin/assets', () =>
        HttpResponse.json(
          {
            type: 'https://nene-serve.dev/problems/asset-rejected',
            title: 'Asset rejected',
            status: 422,
            instance: '/admin/assets',
          },
          { status: 422 },
        ),
      ),
    )

    const { result } = renderHookWithProviders(() => useUploadAsset())

    await act(async () => {
      await expect(
        result.current.mutateAsync({ contentType: 'text/html', dataBase64: 'AAAA' }),
      ).rejects.toSatisfy((error: unknown) => {
        expect(error).toBeInstanceOf(AppError)
        expect((error as AppError).status).toBe(422)
        expect((error as AppError).title).toBe('Asset rejected')
        return true
      })
    })
  })
})
