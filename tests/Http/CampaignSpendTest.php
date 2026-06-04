<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Serving\InMemoryCreativeRepository;
use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Campaign spend (billing §3.1/§3.5): a campaign-bound creative serves and
 * accrues spend only when active + funded; spend is derived (reproducible) and
 * the budget cap pauses paid serving — no overspend. Uses CPC so one click
 * exhausts the budget.
 */
final class CampaignSpendTest extends TestCase
{
    private Kernel $kernel;
    private InMemoryPlacementRepository $placements;
    private InMemoryCreativeRepository $creatives;

    protected function setUp(): void
    {
        $this->placements = new InMemoryPlacementRepository([
            new Placement('plc-mk', 'org-acme', 'pk_mk', [], 'active', null),
        ]);
        $this->creatives = new InMemoryCreativeRepository();
        $this->kernel = new Kernel(
            placements: $this->placements,
            creatives: $this->creatives,
            tokens: new InMemoryTokenStore(),
            events: new InMemoryEventStore(),
        );
    }

    public function testFundedServesThenBudgetCapPausesAfterClick(): void
    {
        $admin = $this->login('admin@acme.test');
        // CPC ¥50/click, budget ¥50 → exhausted after one click.
        $campaign = $this->fundedCampaign($admin, 'cpc', 50, 50);
        $this->approvedCampaignCreative($admin, $campaign);

        // 1st serve: within budget → 200.
        $serve = $this->serve('pk_mk');
        self::assertSame(200, $serve->status);

        // Click → records a billable click → spend reaches the budget.
        $clickUrl = (string) $this->decode($serve->body)['click_url'];
        self::assertSame(302, $this->kernel->handle(new Request('GET', $clickUrl))->status);

        // Derived spend is visible and reproducible.
        $spend = $this->decode($this->get('/admin/campaigns/' . $campaign, $admin)->body)['spend'];
        self::assertSame(1, $spend['billable_clicks']);
        self::assertSame(50, $spend['spent_cents']);
        self::assertTrue($spend['exhausted']);

        // 2nd serve: budget exhausted + pause_on_budget_exhausted → empty 204, no overspend.
        self::assertSame(204, $this->serve('pk_mk')->status);
    }

    public function testUnfundedCampaignDoesNotServe(): void
    {
        $admin = $this->login('admin@acme.test');
        $campaign = $this->campaign($admin, 'cpc', 50, 100000, 'active', 'unfunded');
        $this->approvedCampaignCreative($admin, $campaign);

        self::assertSame(204, $this->serve('pk_mk')->status);
    }

    public function testCampaignRequiresKnownAdvertiserAndPricing(): void
    {
        $admin = $this->login('admin@acme.test');
        $rule = $this->id($this->post('/admin/pricing-rules', $admin, ['name' => 'r', 'pricing_model' => 'cpc', 'rate_cents' => 50]));

        $badAdv = $this->post('/admin/campaigns', $admin, ['advertiser_id' => 'nope', 'name' => 'C', 'pricing_rule_id' => $rule, 'budget_cents' => 100]);
        self::assertSame(422, $badAdv->status);
    }

    private function fundedCampaign(string $admin, string $model, int $rateCents, int $budgetCents): string
    {
        return $this->campaign($admin, $model, $rateCents, $budgetCents, 'active', 'funded');
    }

    private function campaign(string $admin, string $model, int $rateCents, int $budgetCents, string $status, string $funding): string
    {
        $adv = $this->id($this->post('/admin/advertisers', $admin, ['name' => 'Acme Foods']));
        $rule = $this->id($this->post('/admin/pricing-rules', $admin, ['name' => 'r', 'pricing_model' => $model, 'rate_cents' => $rateCents]));

        return $this->id($this->post('/admin/campaigns', $admin, [
            'advertiser_id' => $adv, 'name' => 'C', 'pricing_rule_id' => $rule,
            'budget_cents' => $budgetCents, 'status' => $status, 'funding_status' => $funding,
        ]));
    }

    /** Create + approve a campaign-bound creative and wire it as plc-mk's default. */
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

        $this->placements->save(new Placement('plc-mk', 'org-acme', 'pk_mk', [], 'active', $id));
    }

    private function serve(string $key): Response
    {
        return $this->kernel->handle(new Request('GET', '/public/placements/' . $key . '/serve'));
    }

    private function get(string $path, string $token): Response
    {
        return $this->kernel->handle(new Request('GET', $path, ['authorization' => 'Bearer ' . $token]));
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
