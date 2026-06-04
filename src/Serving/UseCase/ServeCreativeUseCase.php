<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Measurement\VisitorBucket;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\DestinationUrl;
use NeneServe\Serving\Frequency\FrequencyCapStoreInterface;
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
        private readonly FrequencyCapStoreInterface $frequencyCaps,
        private readonly EventStoreInterface $events,
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly GetCampaignSpendUseCase $campaignSpend,
        private readonly int $clickTokenTtlSeconds = 900,
    ) {
    }

    public function execute(
        string $publicPlacementKey,
        ?string $origin,
        bool $consentGranted = false,
        string $clientIp = '',
        string $userAgent = '',
    ): ServeResult {
        $placement = $this->placements->findByPublicKey($publicPlacementKey);
        if ($placement === null) {
            throw new PlacementNotFoundException();
        }

        if (!$placement->allowsOrigin($origin)) {
            throw new OriginNotAllowedException();
        }

        if (!$placement->isActive() || $placement->defaultCreativeId === null) {
            $this->events->recordServeRequest($placement->organizationId, $placement->id, false);
            throw new NoEligibleCreativeException();
        }

        // Frequency cap is consent-gated: only when the placement measures, a cap
        // is set, and consent permits a visitor bucket. No consent → no cap, no
        // tracking (fail open to serve — privacy ADR 0017 §3).
        if ($placement->measurementEnabled
            && $placement->frequencyCap !== null
            && $consentGranted) {
            $bucket = VisitorBucket::derive($placement->organizationId, $clientIp, $userAgent);
            if ($this->frequencyCaps->count($placement->id, $bucket) >= $placement->frequencyCap) {
                $this->events->recordServeRequest($placement->organizationId, $placement->id, false);
                throw new FrequencyCappedException();
            }
        }

        $creative = $this->creatives->findByIdInOrganization(
            $placement->defaultCreativeId,
            $placement->organizationId,
        );
        if ($creative === null
            || !$creative->isServable()
            || !DestinationUrl::isSafe($creative->destinationUrl)) {
            $this->events->recordServeRequest($placement->organizationId, $placement->id, false);
            throw new NoEligibleCreativeException();
        }

        // Marketplace gate (billing §3.1/§3.5): a creative bound to a campaign serves
        // (and is billable) only when the campaign is active + funded and within
        // budget. Otherwise it is a non-billable empty serve — never an overspend.
        if ($creative->campaignId !== null && !$this->campaignAllowsServe($creative->campaignId, $placement->organizationId)) {
            $this->events->recordServeRequest($placement->organizationId, $placement->id, false);
            throw new NoEligibleCreativeException();
        }

        $this->events->recordServeRequest($placement->organizationId, $placement->id, true);

        $clickToken = $this->tokens->issueClickToken(
            $placement->organizationId,
            $placement->id,
            $creative->id,
            $creative->destinationUrl,
            $this->clickTokenTtlSeconds,
        );

        $creativePayload = $creative->toServePayload();

        // HTML5 renders via an opaque, TTL-bound frame token so the public payload
        // never exposes the internal creative id (api-security §2, ADR 0021 §4).
        if ($creative->type === CreativeType::Html5Bundle) {
            $frameToken = $this->tokens->issueFrameToken(
                $placement->organizationId,
                $creative->id,
                $this->clickTokenTtlSeconds,
            );
            $creativePayload['render'] = [
                'mode' => 'iframe_sandboxed',
                'sandbox' => 'allow-scripts',
                'frame_url' => '/public/frames/' . $frameToken,
            ];
        }

        $payload = ['creative' => $creativePayload];

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

    /**
     * A campaign-bound creative may serve only when the campaign is active +
     * funded and (if it auto-pauses) still within budget — derived spend never
     * exceeds budget (no overspend, billing §3.5).
     */
    private function campaignAllowsServe(string $campaignId, string $organizationId): bool
    {
        $campaign = $this->campaigns->findByIdInOrganization($campaignId, $organizationId);
        if ($campaign === null || !$campaign->isFundedForServe()) {
            return false;
        }
        if ($campaign->pauseOnBudgetExhausted && $this->campaignSpend->forCampaign($campaign)->isExhausted()) {
            return false;
        }

        return true;
    }
}
