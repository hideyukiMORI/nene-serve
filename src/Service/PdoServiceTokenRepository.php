<?php

declare(strict_types=1);

namespace NeneServe\Service;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoServiceTokenRepository implements ServiceTokenRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, token_hash, scopes, status';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByPresentedToken(string $presented): ?ServiceToken
    {
        // Match by hash at the boundary (the raw secret is never stored), and only
        // active tokens resolve — revoked tokens are tombstoned, not deleted.
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . " FROM service_tokens
             WHERE token_hash = ? AND status = 'active' LIMIT 1",
            [hash('sha256', $presented)],
        );

        return $row === null ? null : $this->hydrate($row);
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
