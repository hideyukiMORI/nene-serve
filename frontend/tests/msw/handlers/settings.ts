import { http, HttpResponse } from 'msw'

let stored = {
  host: 'mailpit',
  port: 1025,
  username: '',
  from_address: 'no-reply@acme.test',
  from_name: 'Acme',
  encryption: 'none',
  has_password: false,
  configured: true,
}

export const settingsHandlers = [
  http.get('/admin/settings/smtp', () => HttpResponse.json(stored)),
  http.put('/admin/settings/smtp', async ({ request }) => {
    const body = (await request.json()) as {
      host?: string
      port?: number
      from_address?: string
      password?: string
    }
    stored = {
      ...stored,
      host: body.host ?? stored.host,
      port: body.port ?? stored.port,
      from_address: body.from_address ?? stored.from_address,
      has_password: body.password !== undefined ? true : stored.has_password,
      configured: true,
    }
    return HttpResponse.json(stored)
  }),
  http.post('/admin/settings/smtp/test', () =>
    HttpResponse.json({ sent: true, recipient: 'admin@acme.test' }),
  ),
]
