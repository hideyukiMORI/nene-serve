import { describe, expect, it } from 'vitest'
import { mapPlacementDtoToModel } from './mapper'

describe('placement mapper', () => {
  it('maps the wire dto to the ui model', () => {
    expect(
      mapPlacementDtoToModel({
        id: 'plc-1',
        public_placement_key: 'pk_home',
        status: 'active',
        default_creative_id: 'cr-1',
        archived_at: null,
      }),
    ).toEqual({
      id: 'plc-1',
      publicKey: 'pk_home',
      status: 'active',
      defaultCreativeId: 'cr-1',
      archivedAt: null,
    })
  })

  it('defaults a missing default creative to null', () => {
    const model = mapPlacementDtoToModel({
      id: 'plc-2',
      public_placement_key: 'pk_side',
      status: 'active',
    })
    expect(model.defaultCreativeId).toBeNull()
  })
})
