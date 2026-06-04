import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { UploadAssetResultDto } from './api-types'

export interface UploadAssetInput {
  contentType: string
  dataBase64: string
}

export function useUploadAsset(): UseMutationResult<
  UploadAssetResultDto,
  AppError,
  UploadAssetInput
> {
  return useMutation({
    mutationFn: (input) =>
      apiClient.post<UploadAssetResultDto>('/admin/assets', {
        content_type: input.contentType,
        data_base64: input.dataBase64,
      }),
  })
}
