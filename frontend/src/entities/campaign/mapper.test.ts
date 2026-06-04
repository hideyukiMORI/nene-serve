import { describe, expect, it } from 'vitest'
import { mapCampaignDtoToModel } from './mapper'

describe('campaign mapper', () => {
  it('maps the wire dto to the ui model', () => {
    expect(
      mapCampaignDtoToModel({
        id: 'cmp-1',
        advertiser_id: 'adv-1',
        name: 'Spring push',
        pricing_rule_id: 'pr-1',
        budget_cents: 1000000,
        currency: 'JPY',
        status: 'active',
        funding_status: 'funded',
        pause_on_budget_exhausted: true,
      }),
    ).toEqual({
      id: 'cmp-1',
      name: 'Spring push',
      status: 'active',
      fundingStatus: 'funded',
      budgetCents: 1000000,
    })
  })
})
