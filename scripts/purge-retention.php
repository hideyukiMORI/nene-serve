<?php

declare(strict_types=1);

/**
 * Governed retention purge (billing §7, privacy §6, ADR 0022 §7).
 *
 * Run by a **privileged** DB role (with DELETE on impressions/clicks), NEVER the
 * application role — the app role cannot delete governed data by design (#39).
 * Removes ordinary measurement past the privacy window and billing-relevant
 * events past the statutory window, skipping any tenant under a legal hold;
 * spend snapshots and the audit log are never touched. Every run is audited.
 *
 * Credentials come from PURGE_DB_* (falling back to DB_*). Operators are warned
 * before destructive retention action — this is a deliberate, scheduled job.
 *
 * Usage (privileged): php scripts/purge-retention.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Nene2\Database\DatabaseConnectionFactoryInterface;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Measurement\PdoEventStore;
use NeneServe\Retention\PdoLegalHoldRepository;
use NeneServe\Retention\UseCase\PurgeRetentionUseCase;
use NeneServe\Serving\PdoCreativeRepository;

$host = getenv('PURGE_DB_HOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
$port = getenv('PURGE_DB_PORT') ?: (getenv('DB_PORT') ?: '3306');
$name = getenv('DB_DATABASE') ?: 'nene_serve';
$user = getenv('PURGE_DB_USERNAME') ?: (getenv('DB_USERNAME') ?: 'root');
$pass = getenv('PURGE_DB_PASSWORD') ?: (getenv('DB_PASSWORD') ?: '');

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);

$query = new PdoDatabaseQueryExecutor(
    new class ($pdo) implements DatabaseConnectionFactoryInterface {
        public function __construct(private readonly PDO $pdo)
        {
        }

        public function create(): PDO
        {
            return $this->pdo;
        }
    },
    $pdo,
);

$purge = new PurgeRetentionUseCase(
    new PdoEventStore($query),
    new PdoCreativeRepository($query),
    new PdoLegalHoldRepository($pdo),
    new PdoAuditLog($query),
);

$now = gmdate('c');
$orgStmt = $pdo->query('SELECT id FROM organizations');
$orgIds = $orgStmt === false ? [] : $orgStmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($orgIds as $orgId) {
    $result = $purge->execute((string) $orgId, 'system-purge', $now);
    if ($result->blockedByLegalHold) {
        printf("%s: skipped (legal hold active)\n", $orgId);
    } else {
        printf("%s: purged %d event(s)\n", $orgId, $result->purgedEvents);
    }
}

echo "retention purge complete\n";
