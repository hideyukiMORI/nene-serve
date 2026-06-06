<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Support\SqlDialect;

final readonly class PdoSmtpSettingsRepository implements SmtpSettingsRepositoryInterface
{
    private const COLUMNS = 'organization_id, host, port, username, password_encrypted, from_address, from_name, encryption';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function find(string $organizationId): ?SmtpSettingsRecord
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM smtp_settings WHERE organization_id = ? LIMIT 1',
            [$organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function save(SmtpSettingsRecord $record): void
    {
        // Upsert without DELETE privilege (the app role has none): INSERT .. ON
        // DUPLICATE KEY UPDATE, so the single per-org row is created or updated.
        $this->query->execute(
            $this->dialect->upsert(
                'smtp_settings',
                ['organization_id', 'host', 'port', 'username', 'password_encrypted', 'from_address', 'from_name', 'encryption'],
                ['organization_id'],
                ['host', 'port', 'username', 'password_encrypted', 'from_address', 'from_name', 'encryption'],
            ),
            [
                $record->organizationId,
                $record->host,
                $record->port,
                $record->username,
                $record->passwordEncrypted,
                $record->fromAddress,
                $record->fromName,
                $record->encryption,
            ],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): SmtpSettingsRecord
    {
        /** @var 'none'|'starttls'|'tls' $encryption */
        $encryption = (string) $row['encryption'];

        return new SmtpSettingsRecord(
            (string) $row['organization_id'],
            (string) $row['host'],
            (int) $row['port'],
            (string) $row['username'],
            $row['password_encrypted'] !== null ? (string) $row['password_encrypted'] : null,
            (string) $row['from_address'],
            (string) $row['from_name'],
            $encryption,
        );
    }
}
