<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use NeneServe\Retention\LegalHold;
use NeneServe\Retention\UseCase\LegalHoldException;
use NeneServe\Tenant\AuthContext;

interface LegalHoldUseCaseInterface
{
    /** @throws LegalHoldException */
    public function place(AuthContext $actor, string $reason): LegalHold;

    /** @throws LegalHoldException */
    public function release(AuthContext $actor, string $holdId): LegalHold;
}
