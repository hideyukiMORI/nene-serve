import { http, HttpResponse } from 'msw'
import { makeCreativeDto } from '@tests/factories/creative'

/** The full library: every review status, both image and html5 types. */
const creatives = [
  makeCreativeDto({ id: 'cr-acme-banner', type: 'image', review_status: 'approved', version: 3, width: 300, height: 250 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-draft', type: 'image', review_status: 'draft', version: 1, width: 300, height: 250 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-leaderboard', type: 'image', review_status: 'approved', version: 2, width: 728, height: 90 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-square', type: 'image', review_status: 'approved', version: 1, width: 250, height: 250 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-rich', type: 'html5', review_status: 'approved', version: 4, width: 300, height: 600 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-promo', type: 'image', review_status: 'submitted', version: 1, width: 320, height: 50 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-holiday', type: 'html5', review_status: 'in_review', version: 2, width: 970, height: 250 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-pending', type: 'image', review_status: 'submitted', version: 1, width: 300, height: 250 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-flash', type: 'image', review_status: 'rejected', version: 1, width: 300, height: 250, review_reason: 'Trademark misuse in the headline copy.' }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-video', type: 'html5', review_status: 'changes_requested', version: 2, width: 640, height: 360, review_reason: 'Reduce the initial payload below 150 KB.' }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-retarget', type: 'image', review_status: 'draft', version: 1, width: 160, height: 600 }), // prettier-ignore
  makeCreativeDto({ id: 'cr-acme-newyear', type: 'image', review_status: 'approved', version: 1, width: 336, height: 280 }), // prettier-ignore
]

/** Items awaiting a reviewer decision: submitted + in_review. */
const reviewQueue = [
  makeCreativeDto({ id: 'cr-acme-pending', type: 'image', review_status: 'submitted', version: 1 }),
  makeCreativeDto({ id: 'cr-acme-promo', type: 'image', review_status: 'submitted', version: 1 }),
  makeCreativeDto({ id: 'cr-acme-holiday', type: 'html5', review_status: 'in_review', version: 2 }),
  makeCreativeDto({
    id: 'cr-acme-q1launch',
    type: 'image',
    review_status: 'submitted',
    version: 1,
  }),
  makeCreativeDto({
    id: 'cr-acme-banner-v4',
    type: 'image',
    review_status: 'in_review',
    version: 4,
  }),
]

export const creativeHandlers = [
  http.get('/admin/creatives', () =>
    HttpResponse.json({ items: creatives, limit: creatives.length, offset: 0 }),
  ),

  http.get('/admin/review-queue', () =>
    HttpResponse.json({ items: reviewQueue, limit: reviewQueue.length, offset: 0 }),
  ),

  http.post('/admin/creatives', () =>
    HttpResponse.json(makeCreativeDto({ id: 'cr-new', review_status: 'draft' }), { status: 201 }),
  ),

  http.post('/admin/creatives/:id/:action', ({ params }) =>
    HttpResponse.json(makeCreativeDto({ id: String(params['id']), review_status: 'approved' })),
  ),

  http.post('/admin/assets', () =>
    HttpResponse.json(
      {
        id: 'ast-1',
        kind: 'image',
        content_type: 'image/png',
        byte_size: 10,
        asset_url: '/public/assets/ast-1',
      },
      { status: 201 },
    ),
  ),
]
