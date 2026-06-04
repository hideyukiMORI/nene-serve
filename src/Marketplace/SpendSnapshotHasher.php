<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * Tamper-evidence for spend snapshots (billing §7): SHA-256 over the snapshot's
 * substantiating fields. Recomputing from stored values detects any silent edit;
 * a legitimate re-derivation is a new versioned row, not an overwrite.
 */
final class SpendSnapshotHasher
{
    public static function compute(
        string $organizationId,
        string $billingPeriodId,
        int $version,
        int $billableImpressions,
        int $billableClicks,
        string $pricingRuleId,
        int $pricingRuleVersion,
        int $spentCents,
    ): string {
        return hash('sha256', implode("\x1f", [
            $organizationId,
            $billingPeriodId,
            (string) $version,
            (string) $billableImpressions,
            (string) $billableClicks,
            $pricingRuleId,
            (string) $pricingRuleVersion,
            (string) $spentCents,
        ]));
    }

    public static function verify(SpendSnapshot $snapshot): bool
    {
        $expected = self::compute(
            $snapshot->organizationId,
            $snapshot->billingPeriodId,
            $snapshot->version,
            $snapshot->billableImpressions,
            $snapshot->billableClicks,
            $snapshot->pricingRuleId,
            $snapshot->pricingRuleVersion,
            $snapshot->spentCents,
        );

        return hash_equals($expected, $snapshot->hash);
    }
}
