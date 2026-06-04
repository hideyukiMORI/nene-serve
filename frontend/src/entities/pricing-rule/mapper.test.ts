import { describe, expect, it } from 'vitest'
import { mapPricingRuleDtoToModel } from './mapper'

describe('pricing-rule mapper', () => {
  it('maps the wire dto to the ui model', () => {
    expect(
      mapPricingRuleDtoToModel({
        id: 'pr-1',
        name: 'CPM standard',
        pricing_model: 'cpm',
        rate_cents: 50000,
        currency: 'JPY',
        pricing_rule_version: 3,
        created_at: '2026-01-01',
      }),
    ).toEqual({ id: 'pr-1', name: 'CPM standard', model: 'cpm', rateCents: 50000, version: 3 })
  })
})
