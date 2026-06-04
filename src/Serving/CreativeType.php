<?php

declare(strict_types=1);

namespace NeneServe\Serving;

/** Creative asset types (ADR 0013/0021). `third_party_tag` is forbidden. */
enum CreativeType: string
{
    case Image = 'image';
    case Video = 'video';
    case Html5Bundle = 'html5_bundle';
}
