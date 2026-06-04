import { useAdvertisers, type Advertiser } from '@/entities/advertiser'
import { useCampaigns, type Campaign } from '@/entities/campaign'
import { usePricingRules, type PricingRule } from '@/entities/pricing-rule'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface MarketplacePage {
  advertisers: Advertiser[]
  pricingRules: PricingRule[]
  campaigns: Campaign[]
  loading: boolean
  errorKey: MessageKey | null
}

export function useMarketplacePage(): MarketplacePage {
  const advertisers = useAdvertisers()
  const pricingRules = usePricingRules()
  const campaigns = useCampaigns()

  const error = advertisers.error ?? pricingRules.error ?? campaigns.error

  return {
    advertisers: advertisers.data ?? [],
    pricingRules: pricingRules.data ?? [],
    campaigns: campaigns.data ?? [],
    loading: advertisers.isPending || pricingRules.isPending || campaigns.isPending,
    errorKey: error != null ? mapProblemDetailsToMessageKey(error) : null,
  }
}
