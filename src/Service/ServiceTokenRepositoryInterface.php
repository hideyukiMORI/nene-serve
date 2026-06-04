<?php

declare(strict_types=1);

namespace NeneServe\Service;

interface ServiceTokenRepositoryInterface
{
    /** Resolve the active token whose hash matches the presented secret. */
    public function findByPresentedToken(string $presented): ?ServiceToken;
}
