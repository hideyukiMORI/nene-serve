<?php

declare(strict_types=1);

namespace NeneServe\Serving;

/**
 * Malware-scan outcome for an HTML5 bundle (ADR 0021 §4). Only `clean` may
 * proceed to review/serve; `flagged` is blocked. Null for non-bundle creatives.
 */
enum ScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Flagged = 'flagged';
}
