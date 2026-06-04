<?php

declare(strict_types=1);

namespace NeneServe\Serving\Token;

/** Result of recording an impression token. */
final class ImpressionRecord
{
    public function __construct(
        public readonly string $organizationId,
        public readonly string $placementId,
        public readonly string $creativeId,
        /** True when this token was already recorded — the beacon is idempotent. */
        public readonly bool $alreadyRecorded,
    ) {
    }
}
