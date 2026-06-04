<?php

declare(strict_types=1);

namespace NeneServe\Tests\Retention;

use NeneServe\Audit\InMemoryAuditLog;
use NeneServe\Measurement\ClickEvent;
use NeneServe\Measurement\ImpressionEvent;
use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Retention\InMemoryLegalHoldRepository;
use NeneServe\Retention\LegalHold;
use NeneServe\Retention\RetentionPolicy;
use NeneServe\Retention\UseCase\PurgeRetentionUseCase;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\InMemoryCreativeRepository;
use NeneServe\Serving\ReviewStatus;
use PHPUnit\Framework\TestCase;

/**
 * Retention purge (billing §7, privacy §6, ADR 0022 §7): ordinary measurement is
 * purged after the privacy window; billing-relevant events are kept for the
 * statutory window; a legal hold blocks everything; the run is audited. The two
 * regimes are never conflated.
 */
final class PurgeRetentionTest extends TestCase
{
    private const NOW = '2026-06-04';

    public function testPurgesOldOrdinaryButKeepsBillingAndRecent(): void
    {
        $events = new InMemoryEventStore();
        // ordinary creative (no campaign) and a billing-relevant one (campaign).
        $creatives = new InMemoryCreativeRepository([
            $this->creative('cr-ord', null),
            $this->creative('cr-bill', 'cmp-1'),
        ]);

        // Old ordinary (2 years ago) → purged. Old billing (2 years ago) → kept (< 7y).
        $events->recordImpression($this->impression('cr-ord', '2024-06-01T00:00:00+00:00'));
        $events->recordClick($this->click('cr-ord', '2024-06-01T00:00:00+00:00'));
        $events->recordImpression($this->impression('cr-bill', '2024-06-01T00:00:00+00:00'));
        // Recent ordinary (yesterday) → kept.
        $events->recordImpression($this->impression('cr-ord', '2026-06-03T00:00:00+00:00'));

        $audit = new InMemoryAuditLog();
        $purge = new PurgeRetentionUseCase($events, $creatives, new InMemoryLegalHoldRepository(), $audit, new RetentionPolicy(privacyRetentionDays: 400, billingStatutoryYears: 7));

        $result = $purge->execute('org-acme', 'system', self::NOW);

        self::assertFalse($result->blockedByLegalHold);
        self::assertSame(2, $result->purgedEvents, 'old ordinary impression + click purged');

        // Billing-relevant (2y old) and recent ordinary survive; spend math unaffected.
        $counts = $events->billableCountsForCreatives('org-acme', ['cr-bill']);
        self::assertSame(1, $counts['impressions'], 'billing-relevant kept for statutory window');
        $ord = $events->billableCountsForCreatives('org-acme', ['cr-ord']);
        self::assertSame(1, $ord['impressions'], 'recent ordinary kept');

        $actions = array_map(static fn ($e) => $e->action, $audit->allForOrganization('org-acme'));
        self::assertContains('retention.purged', $actions);
    }

    public function testLegalHoldBlocksPurge(): void
    {
        $events = new InMemoryEventStore();
        $events->recordImpression($this->impression('cr-ord', '2000-01-01T00:00:00+00:00'));
        $creatives = new InMemoryCreativeRepository([$this->creative('cr-ord', null)]);
        $holds = new InMemoryLegalHoldRepository([
            new LegalHold('lh-1', 'org-acme', 'litigation', '2026-01-01T00:00:00+00:00'),
        ]);
        $audit = new InMemoryAuditLog();

        $result = (new PurgeRetentionUseCase($events, $creatives, $holds, $audit))->execute('org-acme', 'system', self::NOW);

        self::assertTrue($result->blockedByLegalHold);
        self::assertSame(0, $result->purgedEvents);
        self::assertSame(1, $events->billableCountsForCreatives('org-acme', ['cr-ord'])['impressions'], 'nothing purged under hold');
        self::assertContains('retention.purge_blocked', array_map(static fn ($e) => $e->action, $audit->allForOrganization('org-acme')));
    }

    private function creative(string $id, ?string $campaignId): Creative
    {
        return new Creative($id, 'org-acme', CreativeType::Image, ReviewStatus::Approved, 'https://x.test/l', 'https://x.test/a.png', 300, 250, campaignId: $campaignId);
    }

    private function impression(string $creativeId, string $at): ImpressionEvent
    {
        return new ImpressionEvent('imp-' . bin2hex(random_bytes(4)), 'org-acme', 'plc', $creativeId, $at);
    }

    private function click(string $creativeId, string $at): ClickEvent
    {
        return new ClickEvent('clk-' . bin2hex(random_bytes(4)), 'org-acme', 'plc', $creativeId, $at);
    }
}
