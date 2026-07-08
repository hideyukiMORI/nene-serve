<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\PublicApi;

use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\FundingStatus;
use NeneServe\Marketplace\PricingModel;
use NeneServe\Marketplace\PricingRule;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Measurement\ClickEvent;
use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Measurement\VisitorBucket;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\Frequency\FileFrequencyCapStore;
use NeneServe\Serving\InMemoryCreativeRepository;
use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\PublicApi\ServeCreativeUseCase;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\Token\FileTokenStore;
use NeneServe\Serving\UseCase\FrequencyCappedException;
use NeneServe\Serving\UseCase\NoEligibleCreativeException;
use NeneServe\Serving\UseCase\OriginNotAllowedException;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use NeneServe\Tests\Marketplace\InMemoryCampaignRepository;
use NeneServe\Tests\Marketplace\InMemoryPricingRuleRepository;
use NeneServe\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * The serve resolver is fail-closed and security-critical. Every gate is
 * exercised from both directions: origin allowlist, active/default, the
 * consent-gated frequency-cap boundary, creative servability + safe destination,
 * the marketplace funding/budget gate, and the privacy opt-out on the impression
 * beacon. Driven through real token/frequency/event doubles + in-memory repos.
 */
final class ServeCreativeUseCaseTest extends TestCase
{
    private const ORG = 'org-1';
    private const KEY = 'pk_home';
    private const IP = '203.0.113.5';
    private const UA = 'UA/1';
    /** UTC day of FixedClock's default instant (2026-07-06T09:00:00+00:00). */
    private const DAY = '2026-07-06';

    private string $tokenPath;
    private string $freqPath;
    private FileTokenStore $tokens;
    private FileFrequencyCapStore $frequencyCaps;
    private InMemoryEventStore $events;

    protected function setUp(): void
    {
        $dir = sys_get_temp_dir() . '/serve-uc-' . bin2hex(random_bytes(6));
        $this->tokenPath = $dir . '/t.json';
        $this->freqPath = $dir . '/f.json';
        $this->tokens = new FileTokenStore($this->tokenPath);
        $this->frequencyCaps = new FileFrequencyCapStore($this->freqPath);
        $this->events = new InMemoryEventStore();
    }

    protected function tearDown(): void
    {
        @unlink($this->tokenPath);
        @unlink($this->freqPath);
    }

    /**
     * @param list<Placement>    $placements
     * @param list<Creative>     $creatives
     * @param list<Campaign>     $campaigns
     * @param list<PricingRule>  $rules
     */
    private function serve(array $placements, array $creatives = [], array $campaigns = [], array $rules = []): ServeCreativeUseCase
    {
        $creativeRepo = new InMemoryCreativeRepository($creatives);
        $spend = new GetCampaignSpendUseCase($creativeRepo, $this->events, new InMemoryPricingRuleRepository($rules));

        return new ServeCreativeUseCase(
            new InMemoryPlacementRepository($placements),
            $creativeRepo,
            new InMemoryCampaignRepository($campaigns),
            $this->tokens,
            $this->frequencyCaps,
            $this->events,
            $spend,
            new FixedClock(),
        );
    }

    /** @param list<string> $origins */
    private function placement(array $origins = [], string $status = 'active', ?string $default = 'cr-1', bool $measurement = true, ?int $cap = null): Placement
    {
        return new Placement('plc-1', self::ORG, self::KEY, $origins, $status, $default, $measurement, $cap);
    }

    private function image(string $id = 'cr-1', ReviewStatus $status = ReviewStatus::Approved, string $dest = 'https://adv.example.com/l', ?string $campaignId = null): Creative
    {
        return new Creative($id, self::ORG, CreativeType::Image, $status, $dest, 'https://cdn.example.com/a.png', 300, 250, 1, 'u-1', null, null, null, null, null, null, $campaignId);
    }

    // --- placement gates -----------------------------------------------------

    public function testUnknownPlacementThrows(): void
    {
        $this->expectException(PlacementNotFoundException::class);
        $this->serve([])->execute('pk_missing', null);
    }

    public function testDisallowedOriginThrows(): void
    {
        $this->expectException(OriginNotAllowedException::class);
        $this->serve([$this->placement(['https://ok.com'])], [$this->image()])->execute(self::KEY, 'https://evil.com');
    }

    public function testInactivePlacementIsEmptyServe(): void
    {
        $this->expectException(NoEligibleCreativeException::class);
        $this->serve([$this->placement([], 'draft')], [$this->image()])->execute(self::KEY, null);
    }

    public function testNoDefaultCreativeIsEmptyServe(): void
    {
        $this->expectException(NoEligibleCreativeException::class);
        $this->serve([$this->placement([], 'active', null)], [])->execute(self::KEY, null);
    }

    // --- happy path + privacy opt-out ---------------------------------------

    public function testServesImageWithTokensAndCorsOrigin(): void
    {
        $result = $this->serve([$this->placement(['https://pub.example'])], [$this->image()])
            ->execute(self::KEY, 'https://pub.example', clientIp: self::IP, userAgent: self::UA);

        self::assertSame('image', $result->payload['creative']['type']);
        self::assertArrayHasKey('impression_token', $result->payload);
        self::assertStringStartsWith('/public/clicks/', $result->payload['click_url']);
        self::assertSame('https://pub.example', $result->corsOrigin);
    }

    public function testMeasurementOptOutOmitsImpressionTokenButStillServes(): void
    {
        $result = $this->serve([$this->placement([], 'active', 'cr-1', measurement: false)], [$this->image()])
            ->execute(self::KEY, null);

        self::assertArrayNotHasKey('impression_token', $result->payload);
        self::assertArrayHasKey('click_url', $result->payload);
    }

    public function testNoCorsOriginWhenOriginNotInAllowlist(): void
    {
        // Empty allowlist permits the serve, but echoes no specific CORS origin.
        $result = $this->serve([$this->placement()], [$this->image()])->execute(self::KEY, 'https://any.example');

        self::assertNull($result->corsOrigin);
    }

    // --- creative gates ------------------------------------------------------

    public function testMissingCreativeIsEmptyServe(): void
    {
        $this->expectException(NoEligibleCreativeException::class);
        // default points at cr-1 but no creative seeded
        $this->serve([$this->placement()], [])->execute(self::KEY, null);
    }

    public function testNonApprovedCreativeIsEmptyServe(): void
    {
        $this->expectException(NoEligibleCreativeException::class);
        $this->serve([$this->placement()], [$this->image('cr-1', ReviewStatus::Submitted)])->execute(self::KEY, null);
    }

    public function testUnsafeDestinationIsEmptyServe(): void
    {
        $this->expectException(NoEligibleCreativeException::class);
        $this->serve([$this->placement()], [$this->image('cr-1', ReviewStatus::Approved, 'http://evil.example/x')])->execute(self::KEY, null);
    }

    public function testHtml5CreativeGetsASandboxedFrameUrl(): void
    {
        $html5 = new Creative('cr-1', self::ORG, CreativeType::Html5Bundle, ReviewStatus::Approved, 'https://adv.example.com/l', null, null, null, 1, 'u-1', null, null, null, 'bundle-1', 4096, null, null);

        $result = $this->serve([$this->placement()], [$html5])->execute(self::KEY, null);

        self::assertSame('iframe_sandboxed', $result->payload['creative']['render']['mode']);
        self::assertStringStartsWith('/public/frames/', $result->payload['creative']['render']['frame_url']);
    }

    // --- frequency cap boundary (consent-gated) ------------------------------

    private function bucket(): string
    {
        return VisitorBucket::derive(self::ORG, self::IP, self::UA, self::DAY);
    }

    public function testServesWhenUnderCap(): void
    {
        $this->frequencyCaps->increment('plc-1', $this->bucket()); // count = 1, cap = 2
        $result = $this->serve([$this->placement([], 'active', 'cr-1', true, 2)], [$this->image()])
            ->execute(self::KEY, null, consentGranted: true, clientIp: self::IP, userAgent: self::UA);

        self::assertArrayHasKey('click_url', $result->payload);
    }

    public function testThrowsExactlyAtCap(): void
    {
        $this->frequencyCaps->increment('plc-1', $this->bucket());
        $this->frequencyCaps->increment('plc-1', $this->bucket()); // count = 2, cap = 2

        $this->expectException(FrequencyCappedException::class);
        $this->serve([$this->placement([], 'active', 'cr-1', true, 2)], [$this->image()])
            ->execute(self::KEY, null, consentGranted: true, clientIp: self::IP, userAgent: self::UA);
    }

    public function testCapIgnoredWithoutConsent(): void
    {
        $this->frequencyCaps->increment('plc-1', $this->bucket());
        $this->frequencyCaps->increment('plc-1', $this->bucket()); // at cap, but no consent

        $result = $this->serve([$this->placement([], 'active', 'cr-1', true, 2)], [$this->image()])
            ->execute(self::KEY, null, consentGranted: false, clientIp: self::IP, userAgent: self::UA);

        self::assertArrayHasKey('click_url', $result->payload);
    }

    public function testCapIgnoredWhenMeasurementDisabled(): void
    {
        $this->frequencyCaps->increment('plc-1', $this->bucket());
        $this->frequencyCaps->increment('plc-1', $this->bucket());

        $result = $this->serve([$this->placement([], 'active', 'cr-1', false, 2)], [$this->image()])
            ->execute(self::KEY, null, consentGranted: true, clientIp: self::IP, userAgent: self::UA);

        self::assertArrayHasKey('click_url', $result->payload);
    }

    // --- marketplace funding/budget gate ------------------------------------

    private function campaign(int $budgetCents, FundingStatus $funding = FundingStatus::Funded, bool $pause = true): Campaign
    {
        return new Campaign('cmp-1', self::ORG, 'adv-1', 'Spring', 'pr-1', $budgetCents, 'active', $funding, $pause);
    }

    private function cpcRule(int $rateCents): PricingRule
    {
        return new PricingRule('pr-1', self::ORG, 'CPC', PricingModel::Cpc, $rateCents, 1, '2026-06-06T00:00:00+00:00');
    }

    public function testUnfundedCampaignIsEmptyServe(): void
    {
        $creative = $this->image('cr-1', ReviewStatus::Approved, 'https://adv.example.com/l', 'cmp-1');

        $this->expectException(NoEligibleCreativeException::class);
        $this->serve([$this->placement()], [$creative], [$this->campaign(1000, FundingStatus::Unfunded)], [$this->cpcRule(10)])
            ->execute(self::KEY, null);
    }

    public function testFundedNonPausingCampaignServesEvenWhenSpendIsHigh(): void
    {
        $creative = $this->image('cr-1', ReviewStatus::Approved, 'https://adv.example.com/l', 'cmp-1');
        $this->events->recordClick(new ClickEvent('clk-1', self::ORG, 'plc-1', 'cr-1', '2026-06-06T00:00:00+00:00'));

        // budget 100, rate 100/click, 1 click → spent 100 ≥ budget, but pause=false skips the check.
        $result = $this->serve([$this->placement()], [$creative], [$this->campaign(100, FundingStatus::Funded, false)], [$this->cpcRule(100)])
            ->execute(self::KEY, null);

        self::assertArrayHasKey('click_url', $result->payload);
    }

    public function testFundedPausingCampaignWithinBudgetServes(): void
    {
        $creative = $this->image('cr-1', ReviewStatus::Approved, 'https://adv.example.com/l', 'cmp-1');
        $this->events->recordClick(new ClickEvent('clk-1', self::ORG, 'plc-1', 'cr-1', '2026-06-06T00:00:00+00:00'));

        // spent 100 < budget 1000 → serves.
        $result = $this->serve([$this->placement()], [$creative], [$this->campaign(1000, FundingStatus::Funded, true)], [$this->cpcRule(100)])
            ->execute(self::KEY, null);

        self::assertArrayHasKey('click_url', $result->payload);
    }

    public function testFundedPausingCampaignAtBudgetIsEmptyServe(): void
    {
        $creative = $this->image('cr-1', ReviewStatus::Approved, 'https://adv.example.com/l', 'cmp-1');
        $this->events->recordClick(new ClickEvent('clk-1', self::ORG, 'plc-1', 'cr-1', '2026-06-06T00:00:00+00:00'));

        // spent 100 == budget 100 → exhausted (>=) → empty serve.
        $this->expectException(NoEligibleCreativeException::class);
        $this->serve([$this->placement()], [$creative], [$this->campaign(100, FundingStatus::Funded, true)], [$this->cpcRule(100)])
            ->execute(self::KEY, null);
    }
}
