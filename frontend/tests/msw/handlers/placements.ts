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
]
