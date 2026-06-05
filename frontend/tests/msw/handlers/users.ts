import { http, HttpResponse } from 'msw'

const ORG = 'org-acme'
const users = [
  { id: 'u-admin', organization_id: ORG, email: 'admin@acme.test', role: 'org_admin' },
  { id: 'u-analyst', organization_id: ORG, email: 'analyst@acme.test', role: 'analyst' },
  { id: 'u-mei', organization_id: ORG, email: 'mei@acme.test', role: 'org_admin' },
  { id: 'u-ken', organization_id: ORG, email: 'ken@acme.test', role: 'editor' },
  { id: 'u-sara', organization_id: ORG, email: 'sara@acme.test', role: 'editor' },
  { id: 'u-raj', organization_id: ORG, email: 'raj@acme.test', role: 'analyst' },
  { id: 'u-yuki', organization_id: ORG, email: 'yuki@acme.test', role: 'analyst' },
]

export const userHandlers = [
  http.get('/admin/users', () => HttpResponse.json({ items: users, limit: users.length, offset: 0 })),
  http.post('/admin/users', async ({ request }) => {
    const body = (await request.json()) as { email: string; role: string }
    return HttpResponse.json(
      { id: 'u-new', email: body.email, role: body.role, invite_email_sent: true },
      { status: 201 },
    )
  }),
]
