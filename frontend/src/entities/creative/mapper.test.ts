import { describe, expect, it } from 'vitest'
import { mapCreativeDtoToModel } from './mapper'

describe('creative mapper', () => {
  it('maps the wire dto to the ui model', () => {
    expect(
      mapCreativeDtoToModel({ id: 'cr-1', type: 'image', review_status: 'approved', version: 2 }),
    ).toEqual({ id: 'cr-1', type: 'image', reviewStatus: 'approved', version: 2 })
  })
})
