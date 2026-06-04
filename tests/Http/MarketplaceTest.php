<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Audit\InMemoryAuditLog;
use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Marketplace foundations (Phase 3-A, billing-and-accounting): advertiser +
 * versioned pricing rule, audited, capability-gated, net integer money only.
 */
final class MarketplaceTest extends TestCase
{
    public function testCreateAdvertiserAndPricingRuleAudited(): void
    {
        $audit = new InMemoryAuditLog();
        $kernel = new Kernel(audit: $audit);
        $admin = $this->login($kernel, 'admin@acme.test');

        $adv = $this->post($kernel, '/admin/advertisers', $admin, ['name' => 'Acme Foods']);
        self::assertSame(201, $adv->status);
        self::assertSame('Acme Foods', $this->decode($adv->body)['name']);

        $rule = $this->post($kernel, '/admin/pricing-rules', $admin, [
            'name' => 'standard', 'pricing_model' => 'cpm', 'rate_cents' => 250,
        ]);
        self::assertSame(201, $rule->status);
        $body = $this->decode($rule->body);
        self::assertSame('cpm', $body['pricing_model']);
        self::assertSame(250, $body['rate_cents']);
        self::assertSame('JPY', $body['currency']);
        self::assertSame(1, $body['pricing_rule_version']);

        $actions = array_map(static fn ($e) => $e->action, $audit->allForOrganization('org-acme'));
        self::assertContains('advertiser.created', $actions);
        self::assertContains('pricing_rule.created', $actions);
    }

    public function testPricingRuleChangeCreatesNewVersion(): void
    {
        $kernel = new Kernel();
        $admin = $this->login($kernel, 'admin@acme.test');

        $v1 = $this->decode($this->post($kernel, '/admin/pricing-rules', $admin, ['name' => 'std', 'pricing_model' => 'cpc', 'rate_cents' => 50])->body);
        $v2 = $this->decode($this->post($kernel, '/admin/pricing-rules', $admin, ['name' => 'std', 'pricing_model' => 'cpc', 'rate_cents' => 60])->body);

        self::assertSame(1, $v1['pricing_rule_version']);
        self::assertSame(2, $v2['pricing_rule_version'], 'a change is a new immutable version');
        self::assertNotSame($v1['id'], $v2['id']);
    }

    public function testRejectsBadPricingModelAndNonIntegerRate(): void
    {
        $kernel = new Kernel();
        $admin = $this->login($kernel, 'admin@acme.test');

        self::assertSame(422, $this->post($kernel, '/admin/pricing-rules', $admin, ['name' => 'x', 'pricing_model' => 'weird', 'rate_cents' => 10])->status);
        // Float money is rejected at the type boundary (rate_cents must be int).
        self::assertSame(422, $this->post($kernel, '/admin/pricing-rules', $admin, ['name' => 'x', 'pricing_model' => 'cpm', 'rate_cents' => 10.5])->status);
    }

    public function testMarketplaceRequiresCapability(): void
    {
        $kernel = new Kernel();
        $analyst = $this->login($kernel, 'analyst@acme.test');

        $response = $this->post($kernel, '/admin/advertisers', $analyst, ['name' => 'X']);
        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-capability', $this->decode($response->body)['type']);
    }

    public function testAdvertisersAreTenantScoped(): void
    {
        $kernel = new Kernel();
        $acme = $this->login($kernel, 'admin@acme.test');
        $globex = $this->login($kernel, 'admin@globex.test', 'globex');

        $this->post($kernel, '/admin/advertisers', $acme, ['name' => 'Acme Only']);

        $globexList = $this->decode($this->get($kernel, '/admin/advertisers', $globex)->body);
        self::assertSame([], $globexList['advertisers'], 'tenant isolation');
    }

    private function get(Kernel $kernel, string $path, string $token): Response
    {
        return $kernel->handle(new Request('GET', $path, ['authorization' => 'Bearer ' . $token]));
    }

    /** @param array<string, mixed> $body */
    private function post(Kernel $kernel, string $path, string $token, array $body): Response
    {
        return $kernel->handle(new Request('POST', $path, ['authorization' => 'Bearer ' . $token], [], (string) json_encode($body)));
    }

    private function login(Kernel $kernel, string $email, string $org = 'acme'): string
    {
        $body = (string) json_encode(['organization' => $org, 'email' => $email, 'password' => DevFixtures::PASSWORD]);

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
