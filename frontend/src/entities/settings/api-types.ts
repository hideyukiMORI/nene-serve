export interface SmtpSettingsDto {
  host: string
  port: number
  username: string
  from_address: string
  from_name: string
  encryption: string
  has_password: boolean
  configured?: boolean
}

export interface SmtpTestResultDto {
  sent: boolean
  recipient: string
}
