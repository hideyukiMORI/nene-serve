<?php

declare(strict_types=1);

namespace NeneServe\Measurement\UseCase;

use NeneServe\Measurement\ClickEvent;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Serving\Token\ClickRedirect;
use NeneServe\Serving\Token\TokenStoreInterface;
use NeneServe\Support\Id;

/**
 * Consumes a click token and records the click before the redirect
 * (measurement-spec: a click is the redirect-endpoint hit). The single-use token
 * guarantees the click is counted at most once. Honours opt-out (no event when
 * `measurement_enabled=false`); the redirect itself is essential and always works.
 */
final class RecordClickUseCase
{
    public function __construct(
        private readonly TokenStoreInterface $tokens,
        private readonly EventStoreInterface $events,
        private readonly PlacementRepositoryInterface $placements,
    ) {
    }

    public function execute(string $token, ?string $countryCode = null): ?ClickRedirect
    {
        $redirect = $this->tokens->consumeClickToken($token);
        if ($redirect === null) {
            return null;
        }

        $placement = $this->placements->findByIdInOrganization($redirect->placementId, $redirect->organizationId);
        if ($placement !== null && $placement->measurementEnabled) {
            $this->events->recordClick(new ClickEvent(
                Id::random(16),
                $redirect->organizationId,
                $redirect->placementId,
                $redirect->creativeId,
                gmdate('c'),
                $countryCode,
            ));
        }

        return $redirect;
    }
}
