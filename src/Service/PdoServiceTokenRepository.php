<?php

declare(strict_types=1);

namespace NeneServe\Service;

use PDO;

final class PdoServiceTokenRepository implements ServiceTokenRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, token_hash, scopes, status';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByPresentedToken(string $presented): ?ServiceToken
    {
        // Match by hash at the boundary (the raw secret is never stored), and only
        // active tokens resolve — revoked tokens are tombstoned, not deleted.
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . " FROM service_tokens
             WHERE token_hash = ? AND status = 'active' LIMIT 1",
        );
        $stmt->execute([hash('sha256', $presented)]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ServiceToken
    {
        /** @var list<string> $raw */
        $raw = json_decode((string) $row['scopes'], true) ?: [];
        $scopes = [];
        foreach ($raw as $value) {
            $scope = Scope::tryFrom((string) $value);
            if ($scope !== null) {
                $scopes[] = $scope;
            }
        }

        return new ServiceToken(
            (string) $row['id'],
            (string) $row['organization_id'],
            (string) $row['token_hash'],
            $scopes,
            (string) $row['status'],
        );
    }
}
