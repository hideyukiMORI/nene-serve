export interface SmtpSettings {
  host: string
  port: number
  username: string
  fromAddress: string
  fromName: string
  encryption: string
  hasPassword: boolean
  configured: boolean
}
