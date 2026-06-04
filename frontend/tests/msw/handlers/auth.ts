import { http, HttpResponse } from 'msw'

const USER = {
  id: 'user-acme-admin',
  organization_id: 'org-acme',
  email: 'admin@acme.test',
  role: 'org_admin',
}

export const authHandlers = [
  http.post('/admin/login', async ({ request }) => {
    const body = (await request.json()) as {
      organization?: string
      email?: string
      password?: string
    }

    if (
      body.organization === 'acme' &&
      body.email === 'admin@acme.test' &&
      body.password === 'password'
    ) {
      return HttpResponse.json({ token: 'header.payload.signature', user: USER })
    }

    return HttpResponse.json(
      {
        type: 'https://nene-serve.dev/problems/invalid-credentials',
        title: 'Invalid Credentials',
        status: 401,
        instance: '/admin/login',
        detail: 'Invalid email or password.',
      },
      { status: 401 },
    )
  }),

  http.get('/admin/me', () => HttpResponse.json(USER)),
]
