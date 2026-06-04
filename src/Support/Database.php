<?php

declare(strict_types=1);

namespace NeneServe\Support;

use PDO;

/**
 * PDO factory. Secrets come from the environment only (api-security §6), never
 * from committed config.
 */
final class Database
{
    public static function fromEnv(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_DATABASE') ?: 'nene_serve';
        $user = getenv('DB_USERNAME') ?: 'nene';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
