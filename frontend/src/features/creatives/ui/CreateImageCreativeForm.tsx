import { zodResolver } from '@hookform/resolvers/zod'
import { useState, type ChangeEvent } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useUploadAsset } from '@/entities/asset'
import { useCreateImageCreative } from '@/entities/creative'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

interface Values {
  destinationUrl: string
  width: number
  height: number
}

/** Read a File as base64 (without the data: prefix). */
function readBase64(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onerror = () => {
      reject(new Error('read failed'))
    }
    reader.onload = () => {
      const result = typeof reader.result === 'string' ? reader.result : ''
      resolve(result.includes(',') ? result.slice(result.indexOf(',') + 1) : result)
    }
    reader.readAsDataURL(file)
  })
}

export function CreateImageCreativeForm() {
  const { t } = useTranslation()
  const create = useCreateImageCreative()
  const upload = useUploadAsset()
  const [assetUrl, setAssetUrl] = useState('')
  const [fileError, setFileError] = useState(false)

  const schema = z.object({
    destinationUrl: z.string().trim().min(1, t('form.required')),
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
    defaultValues: { destinationUrl: '', width: 300, height: 250 },
  })

  const onFile = (event: ChangeEvent<HTMLInputElement>): void => {
    const file = event.target.files?.[0]
    if (file === undefined) return
    setFileError(false)
    void readBase64(file)
      .then((dataBase64) => upload.mutateAsync({ contentType: file.type, dataBase64 }))
      .then((result) => {
        setAssetUrl(result.asset_url)
      })
      .catch(() => {
        setFileError(true)
      })
  }

  const submit = handleSubmit(async (values) => {
    if (assetUrl === '') {
      setFileError(true)
      return
    }
    try {
      await create.mutateAsync({ ...values, assetUrl })
      reset()
      setAssetUrl('')
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
      <div className="field">
        <label htmlFor="creative-file">{t('creatives.field.file')}</label>
        <input id="creative-file" type="file" accept="image/*" onChange={onFile} />
        {upload.isPending ? <span className="t-tiny muted">{t('creatives.uploading')}</span> : null}
        {assetUrl !== '' ? <span className="t-tiny muted">{t('creatives.uploaded')}</span> : null}
        {fileError ? <span className="t-tiny danger">{t('creatives.uploadError')}</span> : null}
      </div>
      <Stack direction="horizontal" gap="sm">
        <Input
          id="creative-destination"
          label={t('creatives.field.destination')}
          error={errors.destinationUrl?.message}
          {...register('destinationUrl')}
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
        <Button type="submit" disabled={create.isPending || upload.isPending}>
          {create.isPending ? t('form.creating') : t('form.create')}
        </Button>
      </Stack>
      {create.error !== null ? <Text className="danger">{t('form.error')}</Text> : null}
    </form>
  )
}
