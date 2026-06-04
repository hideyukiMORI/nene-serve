import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { Advertiser } from '@/entities/advertiser'
import { useCreateCampaign } from '@/entities/campaign'
import type { PricingRule } from '@/entities/pricing-rule'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Select, Stack, Text } from '@/shared/ui'

interface Values {
  name: string
  advertiserId: string
  pricingRuleId: string
  budgetCents: number
}

export interface CreateCampaignFormProps {
  advertisers: Advertiser[]
  pricingRules: PricingRule[]
}

export function CreateCampaignForm({ advertisers, pricingRules }: CreateCampaignFormProps) {
  const { t } = useTranslation()
  const create = useCreateCampaign()
  const schema = z.object({
    name: z.string().trim().min(1, t('marketplace.validation.required')),
    advertiserId: z.string().min(1, t('marketplace.validation.required')),
    pricingRuleId: z.string().min(1, t('marketplace.validation.required')),
    budgetCents: z.number().int().min(0, t('marketplace.validation.nonNegative')),
  })
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', advertiserId: '', pricingRuleId: '', budgetCents: 0 },
  })

  const submit = handleSubmit(async (values) => {
    try {
      await create.mutateAsync({
        advertiserId: values.advertiserId,
        name: values.name,
        pricingRuleId: values.pricingRuleId,
        budgetCents: values.budgetCents,
        pauseOnBudgetExhausted: true,
      })
      reset()
    } catch {
      /* surfaced below */
    }
  })

  const disabled = advertisers.length === 0 || pricingRules.length === 0

  return (
    <form
      noValidate
      onSubmit={(event) => {
        void submit(event)
      }}
      className="card stack g3"
    >
      <Text as="h3" variant="heading-sm">
        {t('marketplace.create.campaign')}
      </Text>
      <Stack direction="horizontal" gap="sm">
        <Input
          id="campaign-name"
          label={t('marketplace.column.name')}
          error={errors.name?.message}
          {...register('name')}
        />
        <Select
          id="campaign-advertiser"
          label={t('marketplace.field.advertiser')}
          options={advertisers.map((a) => ({ value: a.id, label: a.name }))}
          error={errors.advertiserId?.message}
          {...register('advertiserId')}
        />
        <Select
          id="campaign-pricing"
          label={t('marketplace.field.pricingRule')}
          options={pricingRules.map((r) => ({ value: r.id, label: r.name }))}
          error={errors.pricingRuleId?.message}
          {...register('pricingRuleId')}
        />
        <Input
          id="campaign-budget"
          type="number"
          label={t('marketplace.field.budgetCents')}
          error={errors.budgetCents?.message}
          {...register('budgetCents', { valueAsNumber: true })}
        />
        <Button type="submit" disabled={create.isPending || disabled}>
          {create.isPending ? t('marketplace.action.creating') : t('marketplace.action.create')}
        </Button>
      </Stack>
      {create.error !== null ? (
        <Text className="danger">{t('marketplace.createError')}</Text>
      ) : null}
    </form>
  )
}
