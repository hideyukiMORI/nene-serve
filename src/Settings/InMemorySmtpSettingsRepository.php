<?php

declare(strict_types=1);

namespace NeneServe\Settings;

final class InMemorySmtpSettingsRepository implements SmtpSettingsRepositoryInterface
{
    /** @var array<string, SmtpSettingsRecord> */
    private array $byOrg = [];

    public function find(string $organizationId): ?SmtpSettingsRecord
    {
        return $this->byOrg[$organizationId] ?? null;
    }

    public function save(SmtpSettingsRecord $record): void
    {
        $this->byOrg[$record->organizationId] = $record;
    }
}
