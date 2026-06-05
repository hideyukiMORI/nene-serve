import { http, HttpResponse } from 'msw'
import { makePlacementDto } from '@tests/factories/placement'

/**
 * A varied operational set: live slots, a paused one, a disabled one, and an
 * archived one. Only `pk_acme_side` has no default creative (em-dash cell).
 */
const placements = [
  makePlacementDto({
    id: 'plc-acme-home',
    public_placement_key: 'pk_acme_home',
    status: 'active',
    default_creative_id: 'cr-acme-banner',
  }),
  makePlacementDto({
    id: 'plc-acme-side',
    public_placement_key: 'pk_acme_side',
    status: 'active',
    default_creative_id: null,
  }),
  makePlacementDto({
    id: 'plc-acme-footer',
    public_placement_key: 'pk_acme_footer',
    status: 'active',
    default_creative_id: 'cr-acme-leaderboard',
  }),
  makePlacementDto({
    id: 'plc-acme-article',
    public_placement_key: 'pk_acme_article_inline',
    status: 'paused',
    default_creative_id: 'cr-acme-square',
  }),
  makePlacementDto({
    id: 'plc-acme-sponsor',
    public_placement_key: 'pk_acme_sponsor_rail',
    status: 'active',
    default_creative_id: 'cr-acme-rich',
  }),
  makePlacementDto({
    id: 'plc-blog-header',
    public_placement_key: 'pk_blog_header',
    status: 'disabled',
    default_creative_id: 'cr-acme-banner',
  }),
  makePlacementDto({
    id: 'plc-news-top',
    public_placement_key: 'pk_news_top',
    status: 'active',
    default_creative_id: 'cr-acme-leaderboard',
  }),
  makePlacementDto({
    id: 'plc-promo-2025',
    public_placement_key: 'pk_promo_legacy',
    status: 'archived',
    default_creative_id: 'cr-acme-square',
    archived_at: '2026-02-18T09:00:00Z',
  }),
]

export const placementHandlers = [
  http.get('/admin/placements', () => HttpResponse.json({ items: placements, limit: placements.length, offset: 0 })),
  http.post('/admin/placements', async ({ request }) => {
    const body = (await request.json()) as { public_placement_key: string }
    return HttpResponse.json(
      makePlacementDto({ id: 'plc-new', public_placement_key: body.public_placement_key }),
      { status: 201 },
    )
  }),
]
