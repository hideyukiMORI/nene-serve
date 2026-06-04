<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * An append-only click record (measurement-spec, ADR 0012). A click is the
 * redirect-endpoint hit; minimized fields only, no raw PII (privacy N6).
 */
final class ClickEvent
{
    public function __construct(
        public readonly string $clickId,
        public readonly string $organizationId,
        public readonly string $placementId,
        public readonly string $creativeId,
        public readonly string $occurredAt,
        public readonly ?string $countryCode = null,
        public readonly ?string $nonBillableReason = null,
    ) {
    }
}
