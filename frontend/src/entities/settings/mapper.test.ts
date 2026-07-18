import { describe, expect, it } from 'vitest'
import { mapSmtpSettingsDtoToModel } from './mapper'

describe('settings mapper', () => {
  it('maps the snake_case wire dto to the camelCase ui model', () => {
    expect(
      mapSmtpSettingsDtoToModel({
        host: 'smtp.acme.test',
        port: 587,
        username: 'mailer',
        from_address: 'noreply@acme.test',
        from_name: 'Acme',
        encryption: 'tls',
        has_password: true,
        configured: true,
      }),
    ).toEqual({
      host: 'smtp.acme.test',
      port: 587,
      username: 'mailer',
      fromAddress: 'noreply@acme.test',
      fromName: 'Acme',
      encryption: 'tls',
      hasPassword: true,
      configured: true,
    })
  })

  it('defaults configured to false when the dto omits it', () => {
    const model = mapSmtpSettingsDtoToModel({
      host: 'smtp.acme.test',
      port: 587,
      username: 'mailer',
      from_address: 'noreply@acme.test',
      from_name: 'Acme',
      encryption: 'tls',
      has_password: false,
    })

    expect(model.configured).toBe(false)
  })
})
