<?php

declare(strict_types=1);

namespace NeneServe\Measurement;

/**
 * An append-only conversion attributed to Serve delivery (ADR 0009 allowed
 * integration: Concierge may log a conversion against a placement). This is a
 * **measurement event**, NOT a Contact submission — no `submission` row, no
 * inbox, no PII (ADR 0009 forbidden). Minimized fields only.
 */
final class ConversionEvent
{
    public function __construct(
        public readonly string $conversionId,
        public readonly string $organizationId,
        public readonly string $placementId,
        public readonly string $occurredAt,
        public readonly ?string $creativeId = null,
        public readonly ?string $countryCode = null,
    ) {
    }
}
