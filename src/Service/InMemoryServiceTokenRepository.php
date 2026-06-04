<?php

declare(strict_types=1);

namespace NeneServe\Service;

final class InMemoryServiceTokenRepository implements ServiceTokenRepositoryInterface
{
    /** @var list<ServiceToken> */
    private array $tokens;

    /** @param list<ServiceToken> $tokens */
    public function __construct(array $tokens = [])
    {
        $this->tokens = $tokens;
    }

    public function findByPresentedToken(string $presented): ?ServiceToken
    {
        foreach ($this->tokens as $token) {
            if ($token->isActive() && $token->matches($presented)) {
                return $token;
            }
        }

        return null;
    }
}
