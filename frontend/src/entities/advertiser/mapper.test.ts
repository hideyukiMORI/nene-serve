import { describe, expect, it } from 'vitest'
import { mapAdvertiserDtoToModel } from './mapper'

describe('advertiser mapper', () => {
  it('maps the wire dto to the ui model', () => {
    expect(mapAdvertiserDtoToModel({ id: 'adv-1', name: 'Acme Ads', status: 'active' })).toEqual({
      id: 'adv-1',
      name: 'Acme Ads',
      status: 'active',
    })
  })
})
