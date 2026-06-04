import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { SmtpSettingsDto, SmtpTestResultDto } from './api-types'
import { mapSmtpSettingsDtoToModel } from './mapper'
import type { SmtpSettings } from './model'
import { settingsKeys } from './query-keys'

export interface UpdateSmtpInput {
  host: string
  port: number
  username: string
  password: string
  fromAddress: string
  fromName: string
  encryption: string
}

export function useUpdateSmtpSettings(): UseMutationResult<
  SmtpSettings,
  AppError,
  UpdateSmtpInput
> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const payload: Record<string, unknown> = {
        host: input.host,
        port: input.port,
        username: input.username,
        from_address: input.fromAddress,
        from_name: input.fromName,
        encryption: input.encryption,
      }
      // Only send the password when the operator entered one (omit to keep stored).
      if (input.password !== '') {
        payload['password'] = input.password
      }
      const dto = await apiClient.put<SmtpSettingsDto>('/admin/settings/smtp', payload)
      return mapSmtpSettingsDtoToModel(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: settingsKeys.all })
    },
  })
}

export function useTestSmtpSettings(): UseMutationResult<SmtpTestResultDto, AppError, void> {
  return useMutation({
    mutationFn: () => apiClient.post<SmtpTestResultDto>('/admin/settings/smtp/test', {}),
  })
}
