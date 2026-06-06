import type { TenantContext } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export interface TenantResolutionCardProps {
  tenant: TenantContext
}

/**
 * Read-only view of how this install routes tenants (ADR 0006). The mode itself
 * is set in deploy configuration (`TENANT_RESOLUTION`); it is not editable from
 * the console — see the tenant-resolution work in #110/#112.
 */
export function TenantResolutionCard({ tenant }: TenantResolutionCardProps) {
  const { t } = useTranslation()

  return (
    <section className="card stack g2" aria-label={t('settings.tenant.title')}>
      <Text variant="heading-sm">{t('settings.tenant.title')}</Text>

      <Stack direction="horizontal" gap="sm">
        <Text muted variant="caption">
          {t('settings.tenant.mode')}
        </Text>
        <Text>{tenant.mode}</Text>
      </Stack>

      {tenant.organization !== null ? (
        <Stack direction="horizontal" gap="sm">
          <Text muted variant="caption">
            {t('settings.tenant.organization')}
          </Text>
          <Text>{tenant.organization.name}</Text>
        </Stack>
      ) : null}

      <Text muted variant="caption">
        {t('settings.tenant.note')}
      </Text>
    </section>
  )
}
