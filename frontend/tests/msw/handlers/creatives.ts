import { http, HttpResponse } from 'msw'
import { makeCreativeDto } from '@tests/factories/creative'

export const creativeHandlers = [
  http.get('/admin/creatives', () =>
    HttpResponse.json({
      creatives: [
        makeCreativeDto({ id: 'cr-acme-banner', review_status: 'approved' }),
        makeCreativeDto({ id: 'cr-acme-draft', review_status: 'draft' }),
      ],
    }),
  ),

  http.get('/admin/review-queue', () =>
    HttpResponse.json({
      creatives: [makeCreativeDto({ id: 'cr-acme-pending', review_status: 'submitted' })],
    }),
  ),

  http.post('/admin/creatives', () =>
    HttpResponse.json(makeCreativeDto({ id: 'cr-new', review_status: 'draft' }), { status: 201 }),
  ),

  http.post('/admin/creatives/:id/:action', ({ params }) =>
    HttpResponse.json(makeCreativeDto({ id: String(params['id']), review_status: 'approved' })),
  ),
]
