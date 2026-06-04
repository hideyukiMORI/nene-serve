import type { SmtpSettingsDto } from './api-types'
import type { SmtpSettings } from './model'

export function mapSmtpSettingsDtoToModel(dto: SmtpSettingsDto): SmtpSettings {
  return {
    host: dto.host,
    port: dto.port,
    username: dto.username,
    fromAddress: dto.from_address,
    fromName: dto.from_name,
    encryption: dto.encryption,
    hasPassword: dto.has_password,
    configured: dto.configured ?? false,
  }
}
