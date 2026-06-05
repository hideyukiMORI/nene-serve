<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

final readonly class ExportVisitorDataInput
{
    public function __construct(
        public string $actorUserId,
        public string $visitorBucket,
    ) {
    }
}
