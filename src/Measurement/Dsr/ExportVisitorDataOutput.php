<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

final readonly class ExportVisitorDataOutput
{
    /** @param list<array{type: string, date: string, placement_id: string, creative_id: string}> $records */
    public function __construct(
        public array $records,
    ) {
    }
}
