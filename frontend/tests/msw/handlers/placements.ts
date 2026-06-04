import { http, HttpResponse } from 'msw'
import { makePlacementDto } from '@tests/factories/placement'

export const placementHandlers = [
  http.get('/admin/placements', () =>
    HttpResponse.json({
      placements: [
        makePlacementDto({ id: 'plc-acme-home', public_placement_key: 'pk_acme_home' }),
        makePlacementDto({
          id: 'plc-acme-side',
          public_placement_key: 'pk_acme_side',
          default_creative_id: null,
        }),
      ],
    }),
  ),
  http.post('/admin/placements', async ({ request }) => {
    const body = (await request.json()) as { public_placement_key: string }
    return HttpResponse.json(
      makePlacementDto({ id: 'plc-new', public_placement_key: body.public_placement_key }),
      { status: 201 },
    )
  }),
]
