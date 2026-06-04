import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useCreatePlacement } from '@/entities/placement'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

interface Values {
  publicKey: string
  origins: string
  defaultCreativeId: string
}

export function CreatePlacementForm() {
  const { t } = useTranslation()
  const create = useCreatePlacement()
  const schema = z.object({
    publicKey: z.string().trim().min(1, t('form.required')),
    origins: z.string(),
    defaultCreativeId: z.string(),
  })
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { publicKey: '', origins: '', defaultCreativeId: '' },
  })

  const submit = handleSubmit(async (values) => {
    const allowedOrigins = values.origins
      .split(',')
      .map((origin) => origin.trim())
      .filter((origin) => origin !== '')
    try {
      await create.mutateAsync({
        publicKey: values.publicKey,
        allowedOrigins,
        defaultCreativeId:
          values.defaultCreativeId.trim() === '' ? null : values.defaultCreativeId.trim(),
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
        {t('placements.create')}
      </Text>
      <Stack direction="horizontal" gap="sm">
        <Input
          id="placement-key"
          label={t('placements.field.key')}
          error={errors.publicKey?.message}
          {...register('publicKey')}
        />
        <Input
          id="placement-origins"
          label={t('placements.field.origins')}
          placeholder="https://example.com, https://www.example.com"
          {...register('origins')}
        />
        <Input
          id="placement-default-creative"
          label={t('placements.field.defaultCreative')}
          {...register('defaultCreativeId')}
        />
        <Button type="submit" disabled={create.isPending}>
          {create.isPending ? t('form.creating') : t('form.create')}
        </Button>
      </Stack>
      {create.error !== null ? <Text className="danger">{t('form.error')}</Text> : null}
    </form>
  )
}
