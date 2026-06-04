import { http, HttpResponse } from 'msw'

export const userHandlers = [
  http.get('/admin/users', () =>
    HttpResponse.json({
      users: [
        { id: 'u-admin', organization_id: 'org-acme', email: 'admin@acme.test', role: 'org_admin' },
        {
          id: 'u-analyst',
          organization_id: 'org-acme',
          email: 'analyst@acme.test',
          role: 'analyst',
        },
      ],
    }),
  ),
  http.post('/admin/users', async ({ request }) => {
    const body = (await request.json()) as { email: string; role: string }
    return HttpResponse.json(
      { id: 'u-new', email: body.email, role: body.role, invite_email_sent: true },
      { status: 201 },
    )
  }),
]
