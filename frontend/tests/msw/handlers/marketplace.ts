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
]
