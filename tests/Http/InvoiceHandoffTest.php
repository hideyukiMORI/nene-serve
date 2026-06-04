<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\InMemorySpendSnapshotRepository;
use NeneServe\Marketplace\Invoice\FakeInvoiceClient;
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
 * Invoice handoff (billing §3.4/§3.5, handoff contract): reconcile → post a
 * net charge; idempotent on external_reference; discrepancies refuse handoff;
 * transport failure is isolated (no double charge, retryable).
 */
final class InvoiceHandoffTest extends TestCase
{
    private InMemoryPlacementRepository $placements;

    public function testHandoffIsNetOnlyAndIdempotent(): void
    {
        $invoice = new FakeInvoiceClient();
        $kernel = $this->kernel(invoice: $invoice);
        $admin = $this->login($kernel, 'admin@acme.test');

        $period = $this->closedPeriodWithOneClick($kernel, $admin);

        $first = $this->post($kernel, '/admin/billing-periods/' . $period . '/handoff', $admin, []);
        self::assertSame(200, $first->status);
        $body = $this->decode($first->body);
        self::assertSame('handed_off', $body['status']);
        self::assertSame('reconciled', $body['reconciliation_status']);
        self::assertSame(50, $body['amount_cents']);
        self::assertNotEmpty($body['invoice_payment_id']);

        // Net-only: the charge carries no tax field; amount is the net spend.
        self::assertCount(1, $invoice->requests);
        self::assertSame(50, $invoice->requests[0]['amount_cents']);
        self::assertArrayNotHasKey('tax_cents', $invoice->requests[0]);

        // Idempotent: a retry returns the same payment id and posts no 2nd charge.
        $second = $this->post($kernel, '/admin/billing-periods/' . $period . '/handoff', $admin, []);
        self::assertSame(200, $second->status);
        self::assertSame($body['invoice_payment_id'], $this->decode($second->body)['invoice_payment_id']);
        self::assertSame(1, $invoice->chargeCount(), 'no double charge');
    }

    public function testTransportFailureIsIsolated(): void
    {
        $kernel = $this->kernel(invoice: new FakeInvoiceClient(fail: true));
        $admin = $this->login($kernel, 'admin@acme.test');
        $period = $this->closedPeriodWithOneClick($kernel, $admin);

        $response = $this->post($kernel, '/admin/billing-periods/' . $period . '/handoff', $admin, []);
        self::assertSame(502, $response->status);
        self::assertStringEndsWith('/problems/invoice-handoff-failed', $this->decode($response->body)['type']);
    }

    public function testReconciliationDiscrepancyRefusesHandoff(): void
    {
        // Seed a tampered snapshot (spent ≠ units × rate) directly.
        $snapshots = new InMemorySpendSnapshotRepository();
        $invoice = new FakeInvoiceClient();
        $kernel = $this->kernel(invoice: $invoice, snapshots: $snapshots);
        $admin = $this->login($kernel, 'admin@acme.test');

        // Build a real campaign + closed period, then inject a bad snapshot for it.
        $period = $this->closedPeriodWithOneClick($kernel, $admin, $snapshots);
        $good = $snapshots->latestForPeriod('org-acme', $period);
        self::assertNotNull($good);
        // Forge a snapshot with a wrong spent but a recomputed (valid) hash, so the
        // hash passes but amount ≠ units × rate → reconciliation must still fail.
        $forgedSpent = $good->spentCents + 999;
        $forgedHash = SpendSnapshotHasher::compute(
            $good->organizationId,
            $good->billingPeriodId,
            $good->version + 1,
            $good->billableImpressions,
            $good->billableClicks,
            $good->pricingRuleId,
            $good->pricingRuleVersion,
            $forgedSpent,
        );
        $snapshots->save(new SpendSnapshot(
            'ss-forged',
            $good->organizationId,
            $good->billingPeriodId,
            $good->version + 1,
            $good->billableImpressions,
            $good->billableClicks,
            $good->pricingRuleId,
            $good->pricingRuleVersion,
            $forgedSpent,
            $forgedHash,
            gmdate('c'),
        ));

        $response = $this->post($kernel, '/admin/billing-periods/' . $period . '/handoff', $admin, []);
        self::assertSame(409, $response->status);
        self::assertStringEndsWith('/problems/reconciliation-failed', $this->decode($response->body)['type']);
        self::assertSame(0, $invoice->chargeCount(), 'discrepancy must not charge');
    }

    public function testHandoffRequiresClosedPeriod(): void
    {
        $kernel = $this->kernel(invoice: new FakeInvoiceClient());
        $admin = $this->login($kernel, 'admin@acme.test');
        $campaign = $this->fundedCpcCampaign($kernel, $admin);
        $period = $this->id($this->post($kernel, '/admin/campaigns/' . $campaign . '/billing-periods', $admin, [
            'period_start' => '2026-06-01', 'period_end' => '2026-06-30',
        ]));

        // Not closed yet → cannot hand off.
        $response = $this->post($kernel, '/admin/billing-periods/' . $period . '/handoff', $admin, []);
        self::assertSame(409, $response->status);
    }

    private function closedPeriodWithOneClick(Kernel $kernel, string $admin, ?InMemorySpendSnapshotRepository $snapshots = null): string
    {
        $campaign = $this->fundedCpcCampaign($kernel, $admin);
        $this->approvedCampaignCreative($kernel, $admin, $campaign);
        $serve = $this->serve($kernel, 'pk_ho');
        $kernel->handle(new Request('GET', (string) $this->decode($serve->body)['click_url']));

        $period = $this->id($this->post($kernel, '/admin/campaigns/' . $campaign . '/billing-periods', $admin, [
            'period_start' => '2026-06-01', 'period_end' => '2026-06-30',
        ]));
        $this->post($kernel, '/admin/billing-periods/' . $period . '/close', $admin, []);

        return $period;
    }

    private function fundedCpcCampaign(Kernel $kernel, string $admin): string
    {
        $adv = $this->id($this->post($kernel, '/admin/advertisers', $admin, ['name' => 'A', 'invoice_client_id' => 'inv-c-1']));
        $rule = $this->id($this->post($kernel, '/admin/pricing-rules', $admin, ['name' => 'r', 'pricing_model' => 'cpc', 'rate_cents' => 50]));

        return $this->id($this->post($kernel, '/admin/campaigns', $admin, [
            'advertiser_id' => $adv, 'name' => 'C', 'pricing_rule_id' => $rule,
            'budget_cents' => 100000, 'status' => 'active', 'funding_status' => 'funded',
        ]));
    }

    private function approvedCampaignCreative(Kernel $kernel, string $admin, string $campaign): void
    {
        $editor = $this->login($kernel, 'editor@acme.test');
        $id = $this->id($this->post($kernel, '/admin/creatives', $editor, [
            'destination_url' => 'https://acme.test/l', 'asset_url' => 'https://cdn.acme.test/x.png',
            'width' => 300, 'height' => 250, 'campaign_id' => $campaign,
        ]));
        $this->post($kernel, '/admin/creatives/' . $id . '/submit', $editor, []);
        $this->post($kernel, '/admin/creatives/' . $id . '/start-review', $admin, []);
        $this->post($kernel, '/admin/creatives/' . $id . '/approve', $admin, []);
        $this->placements->save(new Placement('plc-ho', 'org-acme', 'pk_ho', [], 'active', $id));
    }

    private function kernel(FakeInvoiceClient $invoice, ?InMemorySpendSnapshotRepository $snapshots = null): Kernel
    {
        $this->placements = new InMemoryPlacementRepository([
            new Placement('plc-ho', 'org-acme', 'pk_ho', [], 'active', null),
        ]);

        return new Kernel(
            placements: $this->placements,
            creatives: new InMemoryCreativeRepository(),
            tokens: new InMemoryTokenStore(),
            events: new InMemoryEventStore(),
            spendSnapshots: $snapshots ?? new InMemorySpendSnapshotRepository(),
            invoiceClient: $invoice,
        );
    }

    private function serve(Kernel $kernel, string $key): Response
    {
        return $kernel->handle(new Request('GET', '/public/placements/' . $key . '/serve'));
    }

    /** @param array<string, mixed> $body */
    private function post(Kernel $kernel, string $path, string $token, array $body): Response
    {
        return $kernel->handle(new Request('POST', $path, ['authorization' => 'Bearer ' . $token], [], (string) json_encode($body)));
    }

    private function id(Response $response): string
    {
        return (string) $this->decode($response->body)['id'];
    }

    private function login(Kernel $kernel, string $email): string
    {
        $body = (string) json_encode(['organization' => 'acme', 'email' => $email, 'password' => DevFixtures::PASSWORD]);

        return (string) $this->decode($kernel->handle(new Request('POST', '/admin/login', [], [], $body))->body)['token'];
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
