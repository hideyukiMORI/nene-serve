<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

final readonly class GetCreativeByIdInput
{
    public function __construct(
        public string $id,
    ) {
    }
}
