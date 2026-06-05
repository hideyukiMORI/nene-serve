<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneServe\Measurement\ClickEvent;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\Token\ClickRedirect;
use NeneServe\Serving\Token\TokenStoreInterface;

/**
 * Consumes a click token and records the click before the redirect
 * (measurement-spec: a click is the redirect-endpoint hit). The single-use token
 * guarantees the click is counted at most once. Honours opt-out (no event when
 * `measurement_enabled=false`); the redirect itself is essential and always works.
 */
final readonly class RecordClickUseCase implements RecordClickUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private TokenStoreInterface $tokens,
        private EventStoreInterface $events,
    ) {
    }

    public function execute(string $token, ?string $countryCode = null): ?ClickRedirect
    {
        $redirect = $this->tokens->consumeClickToken($token);

        if ($redirect === null) {
            return null;
        }

        $placement = (new PdoPlacementRepository($this->query))->findByIdInOrganization($redirect->placementId, $redirect->organizationId);

        if ($placement !== null && $placement->measurementEnabled) {
            $this->events->recordClick(new ClickEvent(
                bin2hex(random_bytes(16)),
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
