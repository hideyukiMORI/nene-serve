<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use NeneServe\Retention\UseCase\LegalHoldException;

interface LegalHoldUseCaseInterface
{
    /** @throws LegalHoldException */
    public function place(PlaceLegalHoldInput $input): PlaceLegalHoldOutput;

    /** @throws LegalHoldException */
    public function release(ReleaseLegalHoldInput $input): ReleaseLegalHoldOutput;
}
