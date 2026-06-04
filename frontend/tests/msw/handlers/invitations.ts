import { http, HttpResponse } from 'msw'

export const invitationHandlers = [
  http.get('/admin/invitations/:token', ({ params }) => {
    if (params['token'] === 'good-token') {
      return HttpResponse.json({ email: 'invitee@acme.test' })
    }
    return HttpResponse.json(
      {
        type: 'https://nene-serve.dev/problems/invitation-invalid',
        title: 'Invalid',
        status: 404,
        instance: '/admin/invitations',
      },
      { status: 404 },
    )
  }),
  http.post('/admin/invitations/accept', () => HttpResponse.json({ accepted: true })),
]
