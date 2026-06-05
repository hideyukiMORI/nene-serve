<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Measurement\ImpressionEvent;
use NeneServe\Measurement\PageUrl;
use NeneServe\Measurement\VisitorBucket;
use NeneServe\Serving\Frequency\FrequencyCapStoreInterface;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Token\TokenStoreInterface;

/**
 * Records an impression from a beacon token (measurement-spec). Idempotent
 * (replay never inflates — ADR 0019 §4). Honours opt-out (`measurement_enabled=
 * false` → no event, privacy P2) and consent: the hashed `visitor_bucket` is
 * stored only when consent is granted (privacy P4, ADR 0017 §3); the impression
 * count itself carries no raw PII.
 */
final readonly class RecordImpressionUseCase implements RecordImpressionUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private TokenStoreInterface $tokens,
        private EventStoreInterface $events,
        private FrequencyCapStoreInterface $frequencyCaps,
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

        $placement = (new PdoPlacementRepository($this->query))->findByIdInOrganization($record->placementId, $record->organizationId);

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
            null,
            $consentGranted ? 'granted' : 'denied',
        ));

        // Count toward the frequency cap only for a consent-gated bucket on a
        // capped placement (privacy ADR 0017 §3).
        if ($visitorBucket !== null && $placement->frequencyCap !== null) {
            $this->frequencyCaps->increment($record->placementId, $visitorBucket);
        }
    }
}
