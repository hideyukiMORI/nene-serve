<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\Review\ReviewAction;

final readonly class TransitionCreativeInput
{
    public function __construct(
        public string $actorUserId,
        public string $creativeId,
        public ReviewAction $action,
        public ?string $reason = null,
        public bool $selfApprovalOverride = false,
    ) {
    }
}
