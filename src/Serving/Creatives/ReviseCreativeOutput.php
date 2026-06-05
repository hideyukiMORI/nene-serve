<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\Creative;

final readonly class ReviseCreativeOutput
{
    public function __construct(
        public Creative $creative,
    ) {
    }
}
