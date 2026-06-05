<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Measurement\ConversionEvent;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use NeneServe\Support\Id;

/**
 * Records a conversion attributed to a placement's delivery (ADR 0009). Append-
 * only measurement; **never** a Contact submission. Honours per-placement opt-out
 * (measurement_enabled=false → not recorded). No PII is stored.
 */
final readonly class RecordConversionUseCase implements RecordConversionUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private EventStoreInterface $events,
    ) {
    }

    public function execute(string $publicPlacementKey, ?string $creativeId = null, ?string $countryCode = null): void
    {
        $placement = (new PdoPlacementRepository($this->query))->findByPublicKey($publicPlacementKey);

        if ($placement === null) {
            throw new PlacementNotFoundException();
        }

        if (!$placement->measurementEnabled) {
            return; // opt-out: no tracking beacon counted (privacy P2)
        }

        $this->events->recordConversion(new ConversionEvent(
            Id::random(16),
            $placement->organizationId,
            $placement->id,
            gmdate('c'),
            $creativeId,
            $countryCode,
        ));
    }
}
