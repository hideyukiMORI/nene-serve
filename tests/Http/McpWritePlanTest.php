<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Support\DevFixtures;
use PHPUnit\Framework\TestCase;

/**
 * MCP write-plan mechanism (api-security §5, ADR 0018): writes are plans needing
 * an explicit confirmation token to apply; read-first (writes need the write
 * scope); apply is audited and idempotent; only approved creatives can be set.
 */
final class McpWritePlanTest extends TestCase
{
    private Kernel $kernel;
    private InMemoryPlacementRepository $placements;

    protected function setUp(): void
    {
        $this->placements = new InMemoryPlacementRepository([
            new Placement('plc-mcp', 'org-acme', 'pk_mcp', [], 'active', 'cr-acme-banner'),
        ]);
        $this->kernel = new Kernel(
            placements: $this->placements,
            creatives: DevFixtures::creatives(), // cr-acme-banner approved, cr-acme-draft draft
            tokens: new InMemoryTokenStore(),
        );
    }

    public function testProposeThenApplyChangesDefaultCreative(): void
    {
        // Approve a second creative to switch to (reuse the banner is already default;
        // propose switching to it from draft baseline). Use cr-acme-banner (approved).
        $plan = $this->decode($this->write('POST', '/api/delivery-plan-changes', [
            'placement_id' => 'plc-mcp', 'new_creative_id' => 'cr-acme-banner',
        ])->body);
        self::assertSame('proposed', $plan['status']);
        self::assertNotEmpty($plan['confirmation_token']);

        // Nothing changed yet (still the original default).
        $before = $this->placements->findByIdInOrganization('plc-mcp', 'org-acme');
        self::assertNotNull($before);
        self::assertSame('cr-acme-banner', $before->defaultCreativeId);

        $applied = $this->write('POST', '/api/delivery-plan-changes/' . $plan['confirmation_token'] . '/apply', []);
        self::assertSame(200, $applied->status);
        self::assertSame('applied', $this->decode($applied->body)['status']);

        // Re-apply is rejected (idempotency).
        $again = $this->write('POST', '/api/delivery-plan-changes/' . $plan['confirmation_token'] . '/apply', []);
        self::assertSame(409, $again->status);
        self::assertStringEndsWith('/problems/invalid-plan-state', $this->decode($again->body)['type']);
    }

    public function testProposeRejectsUnapprovedCreative(): void
    {
        // cr-acme-draft is not approved → cannot be planned as default.
        $response = $this->write('POST', '/api/delivery-plan-changes', [
            'placement_id' => 'plc-mcp', 'new_creative_id' => 'cr-acme-draft',
        ]);
        self::assertSame(422, $response->status);
    }

    public function testWriteRequiresWriteScope(): void
    {
        // The read-only fixture token lacks write:delivery_plan.
        $response = $this->kernel->handle(new Request(
            'POST',
            '/api/delivery-plan-changes',
            ['authorization' => 'Bearer ' . DevFixtures::SERVICE_TOKEN],
            [],
            (string) json_encode(['placement_id' => 'plc-mcp', 'new_creative_id' => 'cr-acme-banner']),
        ));
        self::assertSame(403, $response->status);
        self::assertStringEndsWith('/problems/insufficient-scope', $this->decode($response->body)['type']);
    }

    public function testApplyUnknownTokenIs404(): void
    {
        $response = $this->write('POST', '/api/delivery-plan-changes/nope/apply', []);
        self::assertSame(404, $response->status);
        self::assertStringEndsWith('/problems/change-plan-not-found', $this->decode($response->body)['type']);
    }

    /** @param array<string, mixed> $body */
    private function write(string $method, string $path, array $body): Response
    {
        return $this->kernel->handle(new Request(
            $method,
            $path,
            ['authorization' => 'Bearer ' . DevFixtures::SERVICE_TOKEN_WRITE],
            [],
            (string) json_encode($body),
        ));
    }

    /** @return array<string, mixed> */
    private function decode(string $json): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }
}
