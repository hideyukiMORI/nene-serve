import { http, HttpResponse } from 'msw'
import {
  makeAdvertiserDto,
  makeCampaignDto,
  makePricingRuleDto,
} from '@tests/factories/marketplace'

const advertisers = [
  makeAdvertiserDto({ id: 'adv-acme', name: 'Acme Media', status: 'active', invoice_client_id: 'inv-acme' }), // prettier-ignore
  makeAdvertiserDto({ id: 'adv-globex', name: 'Globex Corp', status: 'active', invoice_client_id: 'inv-globex' }), // prettier-ignore
  makeAdvertiserDto({ id: 'adv-umbrella', name: 'Umbrella K.K.', status: 'active', invoice_client_id: null }), // prettier-ignore
  makeAdvertiserDto({ id: 'adv-initech', name: 'Initech', status: 'suspended', invoice_client_id: 'inv-initech' }), // prettier-ignore
  makeAdvertiserDto({ id: 'adv-soylent', name: 'Soylent Inc', status: 'archived', invoice_client_id: null }), // prettier-ignore
]

const pricingRules = [
  makePricingRuleDto({ id: 'pr-cpm', name: 'CPM standard', pricing_model: 'cpm', rate_cents: 50000, pricing_rule_version: 2, created_at: '2026-01-12' }), // prettier-ignore
  makePricingRuleDto({ id: 'pr-cpc', name: 'CPC premium', pricing_model: 'cpc', rate_cents: 12000, pricing_rule_version: 1, created_at: '2026-02-03' }), // prettier-ignore
  makePricingRuleDto({ id: 'pr-flat', name: 'Flat sponsorship', pricing_model: 'flat', rate_cents: 300000000, pricing_rule_version: 1, created_at: '2026-03-20' }), // prettier-ignore
  makePricingRuleDto({ id: 'pr-cpm-promo', name: 'CPM promo', pricing_model: 'cpm', rate_cents: 32000, pricing_rule_version: 3, created_at: '2026-04-08' }), // prettier-ignore
]

const campaigns = [
  makeCampaignDto({ id: 'cmp-spring', name: 'Spring push', advertiser_id: 'adv-acme', pricing_rule_id: 'pr-cpm', status: 'active', funding_status: 'funded', budget_cents: 100000000 }), // prettier-ignore
  makeCampaignDto({ id: 'cmp-summer', name: 'Summer sale', advertiser_id: 'adv-globex', pricing_rule_id: 'pr-cpc', status: 'active', funding_status: 'funded', budget_cents: 250000000 }), // prettier-ignore
  makeCampaignDto({ id: 'cmp-brand', name: 'Brand awareness', advertiser_id: 'adv-acme', pricing_rule_id: 'pr-flat', status: 'paused', funding_status: 'funded', budget_cents: 500000000 }), // prettier-ignore
  makeCampaignDto({ id: 'cmp-q4', name: 'Q4 launch', advertiser_id: 'adv-umbrella', pricing_rule_id: 'pr-cpm', status: 'draft', funding_status: 'unfunded', budget_cents: 80000000 }), // prettier-ignore
  makeCampaignDto({ id: 'cmp-newprod', name: 'New product teaser', advertiser_id: 'adv-globex', pricing_rule_id: 'pr-cpc', status: 'active', funding_status: 'pending', budget_cents: 150000000 }), // prettier-ignore
  makeCampaignDto({ id: 'cmp-clearance', name: 'Clearance', advertiser_id: 'adv-initech', pricing_rule_id: 'pr-cpm-promo', status: 'archived', funding_status: 'funded', budget_cents: 40000000 }), // prettier-ignore
]

export const marketplaceHandlers = [
  http.get('/admin/advertisers', () =>
    HttpResponse.json({ items: advertisers, limit: advertisers.length, offset: 0 }),
  ),
  http.get('/admin/pricing-rules', () =>
    HttpResponse.json({ items: pricingRules, limit: pricingRules.length, offset: 0 }),
  ),
  http.get('/admin/campaigns', () =>
    HttpResponse.json({ items: campaigns, limit: campaigns.length, offset: 0 }),
  ),

  http.post('/admin/advertisers', async ({ request }) => {
    const body = (await request.json()) as { name: string }
    return HttpResponse.json(makeAdvertiserDto({ id: 'adv-new', name: body.name }), { status: 201 })
  }),
  http.post('/admin/pricing-rules', async ({ request }) => {
    const body = (await request.json()) as {
      name: string
      pricing_model: string
      rate_cents: number
    }
    return HttpResponse.json(
      makePricingRuleDto({
        id: 'pr-new',
        name: body.name,
        pricing_model: body.pricing_model,
        rate_cents: body.rate_cents,
      }),
      { status: 201 },
    )
  }),
  http.post('/admin/campaigns', async ({ request }) => {
    const body = (await request.json()) as { name: string }
    return HttpResponse.json(makeCampaignDto({ id: 'cmp-new', name: body.name }), { status: 201 })
  }),
]
