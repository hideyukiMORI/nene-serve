import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useCreateAdvertiser } from '@/entities/advertiser'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

interface Values {
  name: string
}

export function CreateAdvertiserForm() {
  const { t } = useTranslation()
  const create = useCreateAdvertiser()
  const schema = z.object({ name: z.string().trim().min(1, t('marketplace.validation.required')) })
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<Values>({ resolver: zodResolver(schema), defaultValues: { name: '' } })

  const submit = handleSubmit(async (values) => {
    try {
      await create.mutateAsync({ name: values.name })
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
        {t('marketplace.create.advertiser')}
      </Text>
      <Stack direction="horizontal" gap="sm">
        <Input
          id="advertiser-name"
          label={t('marketplace.column.name')}
          error={errors.name?.message}
          {...register('name')}
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
