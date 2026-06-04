<?php

declare(strict_types=1);

namespace NeneServe\Measurement\UseCase;

use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Measurement\ImpressionEvent;
use NeneServe\Measurement\PageUrl;
use NeneServe\Measurement\VisitorBucket;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Serving\Token\TokenStoreInterface;

/**
 * Records an impression from a beacon token (measurement-spec). Idempotent
 * (replay never inflates — ADR 0019 §4). Honours opt-out (`measurement_enabled=
 * false` → no event, privacy P2) and consent: the hashed `visitor_bucket` is
 * stored only when consent is granted (privacy P4, ADR 0017 §3); the impression
 * count itself carries no raw PII.
 */
final class RecordImpressionUseCase
{
    public function __construct(
        private readonly TokenStoreInterface $tokens,
        private readonly EventStoreInterface $events,
        private readonly PlacementRepositoryInterface $placements,
    ) {
    }

    public function execute(
        string $token,
        string $clientIp,
        string $userAgent,
        bool $consentGranted,
        ?string $countryCode = null,
        ?string $pageUrl = null,
    ): void {
        $record = $this->tokens->recordImpression($token);
        if ($record === null || $record->alreadyRecorded) {
            return; // unknown token, or replay — never double count
        }

        $placement = $this->placements->findByIdInOrganization($record->placementId, $record->organizationId);
        if ($placement === null || !$placement->measurementEnabled) {
            return; // opt-out: served without a tracking beacon
        }

        $visitorBucket = $consentGranted
            ? VisitorBucket::derive($record->organizationId, $clientIp, $userAgent)
            : null;

        $this->events->recordImpression(new ImpressionEvent(
            bin2hex(random_bytes(16)),
            $record->organizationId,
            $record->placementId,
            $record->creativeId,
            gmdate('c'),
            $countryCode,
            PageUrl::truncate($pageUrl),
            $visitorBucket,
        ));
    }
}
