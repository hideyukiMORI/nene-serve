<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement;

use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Measurement\VisitorBucket;
use NeneServe\Serving\Frequency\FileFrequencyCapStore;
use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\PublicApi\RecordImpressionUseCase;
use NeneServe\Serving\Token\FileTokenStore;
use NeneServe\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * Impression recording is privacy- and idempotency-critical: replay must never
 * double-count, opt-out placements record nothing, and the hashed visitor bucket
 * (and its frequency-cap tick) exist ONLY under granted consent. Each branch is
 * driven through the real token / event / frequency-cap doubles.
 */
final class RecordImpressionUseCaseTest extends TestCase
{
    private const ORG = 'org-1';
    private const PLACEMENT = 'plc-1';
    private const CREATIVE = 'cr-1';
    private const IP = '203.0.113.9';
    private const UA = 'Mozilla/5.0';
    /** UTC day of FixedClock's default instant (2026-07-06T09:00:00+00:00). */
    private const DAY = '2026-07-06';

    private string $tokenPath;
    private string $freqPath;
    private FileTokenStore $tokens;
    private FileFrequencyCapStore $frequencyCaps;
    private InMemoryEventStore $events;

    protected function setUp(): void
    {
        $dir = sys_get_temp_dir() . '/serve-impr-' . bin2hex(random_bytes(6));
        $this->tokenPath = $dir . '/tokens.json';
        $this->freqPath = $dir . '/freq.json';
        $this->tokens = new FileTokenStore($this->tokenPath);
        $this->frequencyCaps = new FileFrequencyCapStore($this->freqPath);
        $this->events = new InMemoryEventStore();
    }

    protected function tearDown(): void
    {
        @unlink($this->tokenPath);
        @unlink($this->freqPath);
    }

    private function useCase(Placement $placement): RecordImpressionUseCase
    {
        return new RecordImpressionUseCase(
            new InMemoryPlacementRepository([$placement]),
            $this->tokens,
            $this->events,
            $this->frequencyCaps,
            new FixedClock(),
        );
    }

    private function placement(bool $measurementEnabled = true, ?int $frequencyCap = 5): Placement
    {
        return new Placement(self::PLACEMENT, self::ORG, 'pk_home', [], 'active', null, $measurementEnabled, $frequencyCap);
    }

    private function issueToken(): string
    {
        return $this->tokens->issueImpressionToken(self::ORG, self::PLACEMENT, self::CREATIVE);
    }

    private function impressionCount(): int
    {
        $total = 0;
        foreach ($this->events->dailyMetrics(self::ORG, '2000-01-01', '2100-01-01') as $row) {
            $total += $row->impressions;
        }

        return $total;
    }

    public function testRecordsAnImpressionWithBucketAndCapTickUnderConsent(): void
    {
        $token = $this->issueToken();
        $this->useCase($this->placement())->execute($token, self::IP, self::UA, consentGranted: true);

        self::assertSame(1, $this->impressionCount());

        $bucket = VisitorBucket::derive(self::ORG, self::IP, self::UA, self::DAY);
        self::assertCount(1, $this->events->exportVisitorData(self::ORG, $bucket));
        self::assertSame(1, $this->frequencyCaps->count(self::PLACEMENT, $bucket));
    }

    public function testRecordsImpressionWithoutBucketOrCapTickWhenConsentDenied(): void
    {
        $token = $this->issueToken();
        $this->useCase($this->placement())->execute($token, self::IP, self::UA, consentGranted: false);

        // The count is still recorded (no PII in the count itself)...
        self::assertSame(1, $this->impressionCount());
        // ...but no visitor bucket is stored and the cap is untouched.
        $bucket = VisitorBucket::derive(self::ORG, self::IP, self::UA, self::DAY);
        self::assertCount(0, $this->events->exportVisitorData(self::ORG, $bucket));
        self::assertSame(0, $this->frequencyCaps->count(self::PLACEMENT, $bucket));
    }

    public function testReplayOfTheSameTokenIsIdempotent(): void
    {
        $token = $this->issueToken();
        $useCase = $this->useCase($this->placement());

        $useCase->execute($token, self::IP, self::UA, consentGranted: true);
        $useCase->execute($token, self::IP, self::UA, consentGranted: true);

        self::assertSame(1, $this->impressionCount());
        self::assertSame(1, $this->frequencyCaps->count(self::PLACEMENT, VisitorBucket::derive(self::ORG, self::IP, self::UA, self::DAY)));
    }

    public function testUnknownTokenRecordsNothing(): void
    {
        $this->useCase($this->placement())->execute('not-a-real-token', self::IP, self::UA, consentGranted: true);

        self::assertSame(0, $this->impressionCount());
    }

    public function testOptedOutPlacementRecordsNothing(): void
    {
        $token = $this->issueToken();
        $this->useCase($this->placement(measurementEnabled: false))->execute($token, self::IP, self::UA, consentGranted: true);

        self::assertSame(0, $this->impressionCount());
    }

    public function testUncappedPlacementStoresBucketButDoesNotTickCap(): void
    {
        $token = $this->issueToken();
        $this->useCase($this->placement(frequencyCap: null))->execute($token, self::IP, self::UA, consentGranted: true);

        $bucket = VisitorBucket::derive(self::ORG, self::IP, self::UA, self::DAY);
        self::assertCount(1, $this->events->exportVisitorData(self::ORG, $bucket));
        self::assertSame(0, $this->frequencyCaps->count(self::PLACEMENT, $bucket));
    }
}
