<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use NeneServe\Marketplace\SpendSnapshot;
use NeneServe\Marketplace\SpendSnapshotHasher;
use PHPUnit\Framework\TestCase;

/**
 * Tamper-evidence for spend snapshots (billing §7). The hash must be
 * deterministic, change when ANY substantiating field changes, resist
 * field-boundary collisions, and verify() must reject a mutated row.
 */
final class SpendSnapshotHasherTest extends TestCase
{
    /** @param array<string, int|string> $overrides */
    private function compute(array $overrides = []): string
    {
        $f = $overrides + [
            'org' => 'org-1', 'period' => 'bp-1', 'version' => 1,
            'imp' => 1000, 'clk' => 50, 'rule' => 'pr-1', 'ruleVer' => 2, 'spent' => 12_500,
        ];

        return SpendSnapshotHasher::compute(
            (string) $f['org'],
            (string) $f['period'],
            (int) $f['version'],
            (int) $f['imp'],
            (int) $f['clk'],
            (string) $f['rule'],
            (int) $f['ruleVer'],
            (int) $f['spent'],
        );
    }

    public function testIsDeterministicAndSha256Hex(): void
    {
        self::assertSame($this->compute(), $this->compute());
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $this->compute());
    }

    /** @return iterable<string, array{array<string, int|string>}> */
    public static function eachField(): iterable
    {
        yield 'organizationId' => [['org' => 'org-2']];
        yield 'billingPeriodId' => [['period' => 'bp-2']];
        yield 'version' => [['version' => 2]];
        yield 'billableImpressions' => [['imp' => 1001]];
        yield 'billableClicks' => [['clk' => 51]];
        yield 'pricingRuleId' => [['rule' => 'pr-2']];
        yield 'pricingRuleVersion' => [['ruleVer' => 3]];
        yield 'spentCents' => [['spent' => 12_501]];
    }

    /** @param array<string, int|string> $override */
    #[\PHPUnit\Framework\Attributes\DataProvider('eachField')]
    public function testHashChangesWhenAnyFieldChanges(array $override): void
    {
        self::assertNotSame($this->compute(), $this->compute($override));
    }

    public function testFieldSeparatorPreventsBoundaryCollision(): void
    {
        // 'a'+'b' must not collide with 'ab'+'' across adjacent string fields.
        self::assertNotSame(
            $this->compute(['org' => 'a', 'period' => 'b']),
            $this->compute(['org' => 'ab', 'period' => '']),
        );
    }

    public function testVerifyAcceptsAnIntactSnapshot(): void
    {
        $hash = $this->compute();
        $snapshot = new SpendSnapshot('ss-1', 'org-1', 'bp-1', 1, 1000, 50, 'pr-1', 2, 12_500, $hash, '2026-06-06T00:00:00+00:00');

        self::assertTrue(SpendSnapshotHasher::verify($snapshot));
    }

    public function testVerifyRejectsAMutatedSnapshot(): void
    {
        $hash = $this->compute();
        // spentCents silently bumped from 12_500 to 99_999, hash left stale.
        $tampered = new SpendSnapshot('ss-1', 'org-1', 'bp-1', 1, 1000, 50, 'pr-1', 2, 99_999, $hash, '2026-06-06T00:00:00+00:00');

        self::assertFalse(SpendSnapshotHasher::verify($tampered));
    }
}
