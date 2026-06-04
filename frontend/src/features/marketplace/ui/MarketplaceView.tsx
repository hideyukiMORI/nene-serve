import type { Advertiser } from '@/entities/advertiser'
import type { Campaign } from '@/entities/campaign'
import type { PricingRule } from '@/entities/pricing-rule'
import { useTranslation } from '@/shared/i18n'
import { formatMoneyJpy } from '@/shared/lib/format-money'
import { EmptyState, Stack, Text } from '@/shared/ui'
import { CreateAdvertiserForm } from './CreateAdvertiserForm'
import { CreateCampaignForm } from './CreateCampaignForm'
import { CreatePricingRuleForm } from './CreatePricingRuleForm'

export interface MarketplaceViewProps {
  advertisers: Advertiser[]
  pricingRules: PricingRule[]
  campaigns: Campaign[]
  loading: boolean
  errorMessage: string | null
}

export function MarketplaceView({
  advertisers,
  pricingRules,
  campaigns,
  loading,
  errorMessage,
}: MarketplaceViewProps) {
  const { t, locale } = useTranslation()

  return (
    <Stack gap="lg">
      <Stack gap="xs">
        <Text as="h1" variant="heading-md">
          {t('marketplace.title')}
        </Text>
        <Text muted>{t('marketplace.subtitle')}</Text>
      </Stack>

      {loading ? <Text muted>{t('marketplace.loading')}</Text> : null}
      {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}

      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('marketplace.advertisers.title')}
        </Text>
        <CreateAdvertiserForm />
        {advertisers.length === 0 ? (
          <EmptyState title={t('marketplace.advertisers.empty')} />
        ) : (
          <table className="table">
            <thead>
              <tr>
                <th>{t('marketplace.column.name')}</th>
                <th>{t('marketplace.column.status')}</th>
              </tr>
            </thead>
            <tbody>
              {advertisers.map((advertiser) => (
                <tr key={advertiser.id}>
                  <td>{advertiser.name}</td>
                  <td>{advertiser.status}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Stack>

      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('marketplace.pricingRules.title')}
        </Text>
        <CreatePricingRuleForm />
        {pricingRules.length === 0 ? (
          <EmptyState title={t('marketplace.pricingRules.empty')} />
        ) : (
          <table className="table">
            <thead>
              <tr>
                <th>{t('marketplace.column.name')}</th>
                <th>{t('marketplace.column.model')}</th>
                <th>{t('marketplace.column.rate')}</th>
                <th>{t('marketplace.column.version')}</th>
              </tr>
            </thead>
            <tbody>
              {pricingRules.map((rule) => (
                <tr key={rule.id}>
                  <td>{rule.name}</td>
                  <td>{rule.model}</td>
                  <td>{formatMoneyJpy(rule.rateCents, locale)}</td>
                  <td>{rule.version}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Stack>

      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('marketplace.campaigns.title')}
        </Text>
        <CreateCampaignForm advertisers={advertisers} pricingRules={pricingRules} />
        {campaigns.length === 0 ? (
          <EmptyState title={t('marketplace.campaigns.empty')} />
        ) : (
          <table className="table">
            <thead>
              <tr>
                <th>{t('marketplace.column.name')}</th>
                <th>{t('marketplace.column.status')}</th>
                <th>{t('marketplace.column.funding')}</th>
                <th>{t('marketplace.column.budget')}</th>
              </tr>
            </thead>
            <tbody>
              {campaigns.map((campaign) => (
                <tr key={campaign.id}>
                  <td>{campaign.name}</td>
                  <td>{campaign.status}</td>
                  <td>{campaign.fundingStatus}</td>
                  <td>{formatMoneyJpy(campaign.budgetCents, locale)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Stack>
    </Stack>
  )
}
