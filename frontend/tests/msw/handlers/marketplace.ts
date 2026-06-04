import { http, HttpResponse } from 'msw'
import {
  makeAdvertiserDto,
  makeCampaignDto,
  makePricingRuleDto,
} from '@tests/factories/marketplace'

export const marketplaceHandlers = [
  http.get('/admin/advertisers', () =>
    HttpResponse.json({ advertisers: [makeAdvertiserDto({ id: 'adv-acme', name: 'Acme Media' })] }),
  ),
  http.get('/admin/pricing-rules', () =>
    HttpResponse.json({
      pricing_rules: [makePricingRuleDto({ id: 'pr-cpm', name: 'CPM standard' })],
    }),
  ),
  http.get('/admin/campaigns', () =>
    HttpResponse.json({ campaigns: [makeCampaignDto({ id: 'cmp-spring', name: 'Spring push' })] }),
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
