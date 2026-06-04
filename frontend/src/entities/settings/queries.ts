import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { SmtpSettingsDto } from './api-types'
import { mapSmtpSettingsDtoToModel } from './mapper'
import type { SmtpSettings } from './model'
import { settingsKeys } from './query-keys'

export function useSmtpSettings(): UseQueryResult<SmtpSettings, AppError> {
  return useQuery({
    queryKey: settingsKeys.smtp(),
    queryFn: async () =>
      mapSmtpSettingsDtoToModel(await apiClient.get<SmtpSettingsDto>('/admin/settings/smtp')),
  })
}
