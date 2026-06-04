<?php

declare(strict_types=1);

namespace NeneServe\Serving;

/**
 * Creative review state machine (ADR 0020). Only `approved` is servable; the
 * transition rules and four-eyes approval are implemented in #13.
 */
enum ReviewStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ChangesRequested = 'changes_requested';

    /** Only an approved creative may ever be served (ADR 0020 §2). */
    public function isServable(): bool
    {
        return $this === self::Approved;
    }

    /** Editable states; `submitted` onward locks the asset (ADR 0020 §1). */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::ChangesRequested;
    }
}
