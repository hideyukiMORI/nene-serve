<?php

declare(strict_types=1);

namespace NeneServe\Retention\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Retention\LegalHoldRepositoryInterface;
use NeneServe\Retention\RetentionPolicy;
use NeneServe\Serving\CreativeRepositoryInterface;

/**
 * The governed retention purge (billing §7, privacy §6, ADR 0022 §7). Run by the
 * **privileged role** (CLI), never the app HTTP role.
 *
 * Removes only events past retention: ordinary measurement after the privacy
 * window, billing-relevant events only after the statutory window — and **never**
 * while a legal hold is active. Spend snapshots, audit events, and any in-window
 * billing-relevant data are untouched. The purge itself is audited; the two
 * retention regimes are kept distinct.
 */
final class PurgeRetentionUseCase
{
    public function __construct(
        private readonly EventStoreInterface $events,
        private readonly CreativeRepositoryInterface $creatives,
        private readonly LegalHoldRepositoryInterface $legalHolds,
        private readonly AuditLogInterface $audit,
        private readonly RetentionPolicy $policy = new RetentionPolicy(),
    ) {
    }

    /**
     * @param string $actorUserId the privileged operator/system identity running the purge
     */
    public function execute(string $organizationId, string $actorUserId, string $now): PurgeResult
    {
        if ($this->legalHolds->hasActiveHold($organizationId)) {
            $this->audit->record(
                $organizationId,
                $actorUserId,
                'retention.purge_blocked',
                'organization',
                $organizationId,
                ['reason' => 'legal_hold_active'],
            );

            return new PurgeResult(true, 0);
        }

        $ordinaryBefore = $this->policy->ordinaryCutoff($now);
        $billingBefore = $this->policy->billingCutoff($now);
        $billingCreativeIds = $this->creatives->idsWithCampaign($organizationId);

        $purged = $this->events->purgeExpiredEvents($organizationId, $ordinaryBefore, $billingBefore, $billingCreativeIds);

        $this->audit->record(
            $organizationId,
            $actorUserId,
            'retention.purged',
            'organization',
            $organizationId,
            [
                'ordinary_before' => $ordinaryBefore,
                'billing_before' => $billingBefore,
                'purged_events' => $purged,
            ],
        );

        return new PurgeResult(false, $purged);
    }
}
