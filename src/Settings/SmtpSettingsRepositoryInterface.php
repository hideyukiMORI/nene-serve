<?php

declare(strict_types=1);

namespace NeneServe\Settings;

interface SmtpSettingsRepositoryInterface
{
    public function find(string $organizationId): ?SmtpSettingsRecord;

    public function save(SmtpSettingsRecord $record): void;
}
