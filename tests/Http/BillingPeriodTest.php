<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\InMemorySpendSnapshotRepository;
use NeneServe\Marketplace\SpendSnapshot;
use NeneServe\Marketplace\SpendSnapshotHasher;
use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Serving\InMemoryCreativeRepository;
use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Billing period close (billing §3.2/§3.3/§7): close freezes a versioned,
 * tamper-evident, reproducible spend snapshot; the period becomes immutable
 * (re-close rejected).
 */
final class BillingPeriodTest extends TestCase
{
    private Kernel $kernel;
    private InMemoryPlacementRepository $placements;
    private InMemorySpendSnapshotRepository $snapshots;

    protected function setUp(): void
    {
        $this->placements = new InMemoryPlacementRepository([
            new Placement('plc-bp', 'org-acme', 'pk_bp', [], 'active', null),
        ]);
        $this->snapshots = new InMemorySpendSnapshotRepository();
        $this->kernel = new Kernel(
            placements: $this->placements,
            creatives: new InMemoryCreativeRepository(),
            tokens: new InMemoryTokenStore(),
            events: new InMemoryEventStore(),
            spendSnapshots: $this->snapshots,
        );
    }

    public function testCloseFreezesReproducibleTamperEvidentSnapshot(): void
    {
        $admin = $this->login('admin@acme.test');
        $campaign = $this->fundedCpcCampaign($admin, 50, 100000);
        $this->approvedCampaignCreative($admin, $campaign);

        // One click → one billable click → spend ¥50.
        $serve = $this->serve('pk_bp');
        $this->kernel->handle(new Request('GET', (string) $this->decode($serve->body)['click_url']));

        $period = $this->id($this->post('/admin/campaigns/' . $campaign . '/billing-periods', $admin, [
            'period_start' => '2026-06-01', 'period_end' => '2026-06-30',
        ]));

        $closed = $this->post('/admin/billing-periods/' . $period . '/close', $admin, []);
        self::assertSame(200, $closed->status);
        $body = $this->decode($closed->body);
        self::assertSame('closed', $body['period']['status']);

        $snap = $body['snapshot'];
        self::assertSame(1, $snap['version']);
        self::assertSame(1, $snap['billable_clicks']);
        self::assertSame(50, $snap['spent_cents']);
        self::assertArrayHasKey('pricing_rule_version', $snap);

        // Reproducible: amount = f(units, pricing_rule_version) (CPC ¥50 × 1 click).
        // Tamper-evident: the stored snapshot verifies against its hash.
        $stored = $this->snapshots->latestForPeriod('org-acme', $period);
        self::assertInstanceOf(SpendSnapshot::class, $stored);
        self::assertTrue(SpendSnapshotHasher::verify($stored));

        // A mutated copy fails verification (detects silent edits).
        $tampered = new SpendSnapshot(
            $stored->id,
            $stored->organizationId,
            $stored->billingPeriodId,
            $stored->version,
            $stored->billableImpressions,
            $stored->billableClicks + 5,
            $stored->pricingRuleId,
            $stored->pricingRuleVersion,
            $stored->spentCents,
            $stored->hash,
            $stored->createdAt,
        );
        self::assertFalse(SpendSnapshotHasher::verify($tampered));
    }

    public function testReclosingIsRejected(): void
    {
        $admin = $this->login('admin@acme.test');
        $campaign = $this->fundedCpcCampaign($admin, 50, 100000);
        $period = $this->id($this->post('/admin/campaigns/' . $campaign . '/billing-periods', $admin, [
            'period_start' => '2026-06-01', 'period_end' => '2026-06-30',
        ]));

        self::assertSame(200, $this->post('/admin/billing-periods/' . $period . '/close', $admin, [])->status);
        $again = $this->post('/admin/billing-periods/' . $period . '/close', $admin, []);
        self::assertSame(409, $again->status);
        self::assertStringEndsWith('/problems/invalid-period-transition', $this->decode($again->body)['type']);
    }

    private function fundedCpcCampaign(string $admin, int $rateCents, int $budgetCents): string
    {
        $adv = $this->id($this->post('/admin/advertisers', $admin, ['name' => 'A']));
        $rule = $this->id($this->post('/admin/pricing-rules', $admin, ['name' => 'r', 'pricing_model' => 'cpc', 'rate_cents' => $rateCents]));

        return $this->id($this->post('/admin/campaigns', $admin, [
            'advertiser_id' => $adv, 'name' => 'C', 'pricing_rule_id' => $rule,
            'budget_cents' => $budgetCents, 'status' => 'active', 'funding_status' => 'funded',
        ]));
    }

    private function approvedCampaignCreative(string $admin, string $campaign): void
    {
        $editor = $this->login('editor@acme.test');
        $id = $this->id($this->post('/admin/creatives', $editor, [
            'destination_url' => 'https://acme.test/l', 'asset_url' => 'https://cdn.acme.test/x.png',
            'width' => 300, 'height' => 250, 'campaign_id' => $campaign,
        ]));
        $this->post('/admin/creatives/' . $id . '/submit', $editor, []);
        $this->post('/admin/creatives/' . $id . '/start-review', $admin, []);
        $this->post('/admin/creatives/' . $id . '/approve', $admin, []);
        $this->placements->save(new Placement('plc-bp', 'org-acme', 'pk_bp', [], 'active', $id));
    }

    private function serve(string $key): Response
    {
        return $this->kernel->handle(new Request('GET', '/public/placements/' . $key . '/serve'));
    }

    /** @param array<string, mixed> $body */
    private function post(string $path, string $token, array $body): Response
    {
        return $this->kernel->handle(new Request('POST', $path, ['authorization' => 'Bearer ' . $token], [], (string) json_encode($body)));
    }

    private function id(Response $response): string
    {
        return (string) $this->decode($response->body)['id'];
    }

    private function login(string $email): string
    {
        $body = (string) json_encode(['organization' => 'acme', 'email' => $email, 'password' => DevFixtures::PASSWORD]);

        return (string) $this->decode($this->kernel->handle(new Request('POST', '/admin/login', [], [], $body))->body)['token'];
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
