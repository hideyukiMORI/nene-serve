import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useCreatePricingRule } from '@/entities/pricing-rule'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Select, Stack, Text } from '@/shared/ui'

interface Values {
  name: string
  model: string
  rateCents: number
}

const MODELS = ['cpm', 'cpc', 'flat']

export function CreatePricingRuleForm() {
  const { t } = useTranslation()
  const create = useCreatePricingRule()
  const schema = z.object({
    name: z.string().trim().min(1, t('marketplace.validation.required')),
    model: z.string().min(1, t('marketplace.validation.required')),
    rateCents: z.number().int().min(0, t('marketplace.validation.nonNegative')),
  })
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', model: 'cpm', rateCents: 0 },
  })

  const submit = handleSubmit(async (values) => {
    try {
      await create.mutateAsync({
        name: values.name,
        model: values.model,
        rateCents: values.rateCents,
      })
      reset()
    } catch {
      /* surfaced below */
    }
  })

  return (
    <form
      noValidate
      onSubmit={(event) => {
        void submit(event)
      }}
      className="card stack g3"
    >
      <Text as="h3" variant="heading-sm">
        {t('marketplace.create.pricingRule')}
      </Text>
      <Stack direction="horizontal" gap="sm">
        <Input
          id="pricing-name"
          label={t('marketplace.column.name')}
          error={errors.name?.message}
          {...register('name')}
        />
        <Select
          id="pricing-model"
          label={t('marketplace.column.model')}
          options={MODELS.map((m) => ({ value: m, label: m.toUpperCase() }))}
          error={errors.model?.message}
          {...register('model')}
        />
        <Input
          id="pricing-rate"
          type="number"
          label={t('marketplace.field.rateCents')}
          error={errors.rateCents?.message}
          {...register('rateCents', { valueAsNumber: true })}
        />
        <Button type="submit" disabled={create.isPending}>
          {create.isPending ? t('marketplace.action.creating') : t('marketplace.action.create')}
        </Button>
      </Stack>
      {create.error !== null ? (
        <Text className="danger">{t('marketplace.createError')}</Text>
      ) : null}
    </form>
  )
}
