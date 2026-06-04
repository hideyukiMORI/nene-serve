<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * An append-only impression record (measurement-spec, ADR 0012). Carries only
 * minimized fields — no raw PII (privacy P4/N6): the optional visitor identifier
 * is a hashed {@see VisitorBucket}, the page URL is truncated, and location is a
 * country code only.
 */
final class ImpressionEvent
{
    public function __construct(
        public readonly string $impressionId,
        public readonly string $organizationId,
        public readonly string $placementId,
        public readonly string $creativeId,
        public readonly string $occurredAt,
        public readonly ?string $countryCode = null,
        public readonly ?string $placementPageUrl = null,
        public readonly ?string $visitorBucket = null,
        public readonly ?string $nonBillableReason = null,
    ) {
    }
}
