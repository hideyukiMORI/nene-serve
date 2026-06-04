<?php

declare(strict_types=1);

namespace NeneServe\Serving\Token;

/** Result of consuming a valid click token. */
final class ClickRedirect
{
    public function __construct(
        public readonly string $organizationId,
        public readonly string $placementId,
        public readonly string $creativeId,
        public readonly string $destinationUrl,
    ) {
    }
}
