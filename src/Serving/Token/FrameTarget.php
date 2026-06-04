<?php

declare(strict_types=1);

namespace NeneServe\Serving\Token;

/** Resolved target of an HTML5 frame token: which tenant + creative to render. */
final class FrameTarget
{
    public function __construct(
        public readonly string $organizationId,
        public readonly string $creativeId,
    ) {
    }
}
