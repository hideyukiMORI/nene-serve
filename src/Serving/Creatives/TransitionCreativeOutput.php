<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\Creative;

final readonly class TransitionCreativeOutput
{
    public function __construct(
        public Creative $creative,
    ) {
    }
}
