import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useCreateImageCreative } from '@/entities/creative'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

interface Values {
  destinationUrl: string
  assetUrl: string
  width: number
  height: number
}

export function CreateImageCreativeForm() {
  const { t } = useTranslation()
  const create = useCreateImageCreative()
  const schema = z.object({
    destinationUrl: z.string().trim().min(1, t('form.required')),
    assetUrl: z.string().trim().min(1, t('form.required')),
    width: z.number().int().positive(t('form.positiveInt')),
    height: z.number().int().positive(t('form.positiveInt')),
  })
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { destinationUrl: '', assetUrl: '', width: 300, height: 250 },
  })

  const submit = handleSubmit(async (values) => {
    try {
      await create.mutateAsync(values)
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
        {t('creatives.create')}
      </Text>
      <Stack direction="horizontal" gap="sm">
        <Input
          id="creative-destination"
          label={t('creatives.field.destination')}
          error={errors.destinationUrl?.message}
          {...register('destinationUrl')}
        />
        <Input
          id="creative-asset"
          label={t('creatives.field.asset')}
          error={errors.assetUrl?.message}
          {...register('assetUrl')}
        />
        <Input
          id="creative-width"
          type="number"
          label={t('creatives.field.width')}
          error={errors.width?.message}
          {...register('width', { valueAsNumber: true })}
        />
        <Input
          id="creative-height"
          type="number"
          label={t('creatives.field.height')}
          error={errors.height?.message}
          {...register('height', { valueAsNumber: true })}
        />
        <Button type="submit" disabled={create.isPending}>
          {create.isPending ? t('form.creating') : t('form.create')}
        </Button>
      </Stack>
      {create.error !== null ? <Text className="danger">{t('form.error')}</Text> : null}
    </form>
  )
}
