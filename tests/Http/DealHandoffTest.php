<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Support\DevFixtures;
use NeneServe\Upstream\Deal\FakeDealClient;
use PHPUnit\Framework\TestCase;

/**
 * Deal opportunity handoff (sibling map Phase 4, ADR 0002): net-only, idempotent
 * on external_reference, audited, transport failure isolated.
 */
final class DealHandoffTest extends TestCase
{
    public function testHandoffIsNetOnlyAndIdempotent(): void
    {
        $deal = new FakeDealClient();
        $kernel = new Kernel(dealClient: $deal);
        $admin = $this->login($kernel, 'admin@acme.test');
        $campaign = $this->campaign($kernel, $admin);

        $first = $this->post($kernel, '/admin/campaigns/' . $campaign . '/deal-handoff', $admin, []);
        self::assertSame(200, $first->status);
        $body = $this->decode($first->body);
        self::assertSame('sent', $body['status']);
        self::assertNotEmpty($body['opportunity_id']);

        // Net-only: budget amount, no tax field.
        self::assertCount(1, $deal->requests);
        self::assertSame(500000, $deal->requests[0]['amount_cents']);
        self::assertArrayNotHasKey('tax_cents', $deal->requests[0]);

        // Idempotent: retry returns same opportunity, no duplicate.
        $second = $this->post($kernel, '/admin/campaigns/' . $campaign . '/deal-handoff', $admin, []);
        self::assertSame($body['opportunity_id'], $this->decode($second->body)['opportunity_id']);
        self::assertSame(1, $deal->opportunityCount(), 'no duplicate opportunity');
    }

    public function testTransportFailureIsIsolated(): void
    {
        $kernel = new Kernel(dealClient: new FakeDealClient(fail: true));
        $admin = $this->login($kernel, 'admin@acme.test');
        $campaign = $this->campaign($kernel, $admin);

        $response = $this->post($kernel, '/admin/campaigns/' . $campaign . '/deal-handoff', $admin, []);
        self::assertSame(502, $response->status);
        self::assertStringEndsWith('/problems/deal-handoff-failed', $this->decode($response->body)['type']);
    }

    public function testUnknownCampaignIs404(): void
    {
        $kernel = new Kernel(dealClient: new FakeDealClient());
        $admin = $this->login($kernel, 'admin@acme.test');

        $response = $this->post($kernel, '/admin/campaigns/nope/deal-handoff', $admin, []);
        self::assertSame(404, $response->status);
        self::assertStringEndsWith('/problems/campaign-not-found', $this->decode($response->body)['type']);
    }

    private function campaign(Kernel $kernel, string $admin): string
    {
        $adv = $this->id($this->post($kernel, '/admin/advertisers', $admin, ['name' => 'Acme Foods']));
        $rule = $this->id($this->post($kernel, '/admin/pricing-rules', $admin, ['name' => 'r', 'pricing_model' => 'cpm', 'rate_cents' => 250]));

        return $this->id($this->post($kernel, '/admin/campaigns', $admin, [
            'advertiser_id' => $adv, 'name' => 'Big Campaign', 'pricing_rule_id' => $rule,
            'budget_cents' => 500000, 'status' => 'active', 'funding_status' => 'funded',
        ]));
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
