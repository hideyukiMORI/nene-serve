import { MarketplaceView, useMarketplacePage } from '@/features/marketplace'
import { useTranslation } from '@/shared/i18n'

export function MarketplacePage() {
  const page = useMarketplacePage()
  const { t } = useTranslation()

  return (
    <MarketplaceView
      advertisers={page.advertisers}
      pricingRules={page.pricingRules}
      campaigns={page.campaigns}
      loading={page.loading}
      errorMessage={page.errorKey !== null ? t(page.errorKey) : null}
    />
  )
}
