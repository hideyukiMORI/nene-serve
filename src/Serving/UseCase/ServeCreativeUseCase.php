<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\DestinationUrl;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Serving\Token\TokenStoreInterface;

/**
 * Resolves a public placement to an eligible creative and issues the public
 * tokens (api-security §2, ADR 0019/0020).
 *
 * Eligibility is **fail closed**: the placement must be active, have a default
 * creative, the creative must be `approved`, and its destination must be a safe
 * redirect target. Anything else is an empty serve (204, not counted). Full
 * delivery-plan rotation/caps land in #13/#14; this baseline serves the
 * placement's default creative.
 */
final class ServeCreativeUseCase
{
    public function __construct(
        private readonly PlacementRepositoryInterface $placements,
        private readonly CreativeRepositoryInterface $creatives,
        private readonly TokenStoreInterface $tokens,
        private readonly int $clickTokenTtlSeconds = 900,
    ) {
    }

    public function execute(string $publicPlacementKey, ?string $origin): ServeResult
    {
        $placement = $this->placements->findByPublicKey($publicPlacementKey);
        if ($placement === null) {
            throw new PlacementNotFoundException();
        }

        if (!$placement->allowsOrigin($origin)) {
            throw new OriginNotAllowedException();
        }

        if (!$placement->isActive() || $placement->defaultCreativeId === null) {
            throw new NoEligibleCreativeException();
        }

        $creative = $this->creatives->findByIdInOrganization(
            $placement->defaultCreativeId,
            $placement->organizationId,
        );
        if ($creative === null
            || !$creative->isServable()
            || !DestinationUrl::isSafe($creative->destinationUrl)) {
            throw new NoEligibleCreativeException();
        }

        $clickToken = $this->tokens->issueClickToken(
            $placement->organizationId,
            $placement->id,
            $creative->id,
            $creative->destinationUrl,
            $this->clickTokenTtlSeconds,
        );

        $payload = ['creative' => $creative->toServePayload()];

        // Privacy P2/§3: only issue the impression beacon token when the placement
        // opts into measurement. The click redirect is essential and always works.
        if ($placement->measurementEnabled) {
            $payload['impression_token'] = $this->tokens->issueImpressionToken(
                $placement->organizationId,
                $placement->id,
                $creative->id,
            );
        }

        $payload['click_url'] = '/public/clicks/' . $clickToken;

        $corsOrigin = ($origin !== null && $placement->allowedOrigins !== [] && in_array($origin, $placement->allowedOrigins, true))
            ? $origin
            : null;

        return new ServeResult($payload, $corsOrigin);
    }
}
