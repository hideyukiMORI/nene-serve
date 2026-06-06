import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { TenantContext } from '@/entities/auth'
import type { SmtpSettings, UpdateSmtpInput } from '@/entities/settings'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Page, Select, Stack, Text } from '@/shared/ui'
import { TenantResolutionCard } from './TenantResolutionCard'

export interface SettingsViewProps {
  settings: SmtpSettings | null
  loading: boolean
  errorMessage: string | null
  saving: boolean
  saveErrorMessage: string | null
  saved: boolean
  testing: boolean
  testMessage: string | null
  tenant: TenantContext | null
  onSave: (input: UpdateSmtpInput) => Promise<boolean>
  onTest: () => void
}

interface Values {
  host: string
  port: number
  username: string
  password: string
  fromAddress: string
  fromName: string
  encryption: string
}

const ENCRYPTIONS = ['none', 'starttls', 'tls']

export function SettingsView({
  settings,
  loading,
  errorMessage,
  saving,
  saveErrorMessage,
  saved,
  testing,
  testMessage,
  tenant,
  onSave,
  onTest,
}: SettingsViewProps) {
  const { t } = useTranslation()
  const schema = z.object({
    host: z.string().trim().min(1, t('form.required')),
    port: z.number().int().positive(t('form.positiveInt')),
    username: z.string(),
    password: z.string(),
    fromAddress: z.string().trim().min(1, t('form.required')),
    fromName: z.string(),
    encryption: z.string(),
  })
  const { register, handleSubmit, reset, formState } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: {
      host: '',
      port: 587,
      username: '',
      password: '',
      fromAddress: '',
      fromName: '',
      encryption: 'starttls',
    },
  })

  useEffect(() => {
    if (settings !== null) {
      reset({
        host: settings.host,
        port: settings.port,
        username: settings.username,
        password: '',
        fromAddress: settings.fromAddress,
        fromName: settings.fromName,
        encryption: settings.encryption,
      })
    }
  }, [settings, reset])

  const submit = handleSubmit(async (values) => {
    await onSave(values)
  })

  return (
    <Page title={t('settings.title')} subtitle={t('settings.smtp.subtitle')}>
      <Stack gap="md">
        {tenant !== null ? <TenantResolutionCard tenant={tenant} /> : null}

        {loading ? <Text muted>{t('settings.loading')}</Text> : null}
        {errorMessage !== null ? <Text className="danger">{errorMessage}</Text> : null}

        <form
          noValidate
          onSubmit={(event) => {
            void submit(event)
          }}
          className="card stack g3"
        >
          <Stack direction="horizontal" gap="sm">
            <Input
              id="smtp-host"
              label={t('settings.smtp.host')}
              error={formState.errors.host?.message}
              {...register('host')}
            />
            <Input
              id="smtp-port"
              type="number"
              label={t('settings.smtp.port')}
              error={formState.errors.port?.message}
              {...register('port', { valueAsNumber: true })}
            />
            <Select
              id="smtp-encryption"
              label={t('settings.smtp.encryption')}
              options={ENCRYPTIONS.map((e) => ({ value: e, label: e }))}
              {...register('encryption')}
            />
          </Stack>
          <Stack direction="horizontal" gap="sm">
            <Input
              id="smtp-username"
              label={t('settings.smtp.username')}
              {...register('username')}
            />
            <Input
              id="smtp-password"
              type="password"
              label={
                settings?.hasPassword === true
                  ? t('settings.smtp.passwordSet')
                  : t('settings.smtp.password')
              }
              placeholder="••••••••"
              {...register('password')}
            />
          </Stack>
          <Stack direction="horizontal" gap="sm">
            <Input
              id="smtp-from-address"
              label={t('settings.smtp.fromAddress')}
              error={formState.errors.fromAddress?.message}
              {...register('fromAddress')}
            />
            <Input
              id="smtp-from-name"
              label={t('settings.smtp.fromName')}
              {...register('fromName')}
            />
          </Stack>
          <Stack direction="horizontal" gap="sm">
            <Button type="submit" disabled={saving}>
              {saving ? t('form.creating') : t('settings.action.save')}
            </Button>
            <Button
              type="button"
              variant="secondary"
              disabled={testing || settings?.configured !== true}
              onClick={onTest}
            >
              {testing ? t('settings.action.testing') : t('settings.action.test')}
            </Button>
          </Stack>
          {saved ? (
            <Text muted variant="caption">
              {t('settings.saved')}
            </Text>
          ) : null}
          {saveErrorMessage !== null ? <Text className="danger">{saveErrorMessage}</Text> : null}
          {testMessage !== null ? (
            <Text muted variant="caption">
              {testMessage}
            </Text>
          ) : null}
        </form>
      </Stack>
    </Page>
  )
}
