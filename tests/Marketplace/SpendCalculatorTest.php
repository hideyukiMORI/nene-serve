<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use NeneServe\Marketplace\PricingModel;
use NeneServe\Marketplace\SpendCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Spend derivation (billing §3.3): integer cents, reproducible, CPM floors.
 */
final class SpendCalculatorTest extends TestCase
{
    public function testCpmFloors(): void
    {
        // ¥250 per 1000 impressions; 4000 impressions → ¥1000.
        self::assertSame(1000, SpendCalculator::compute(PricingModel::Cpm, 250, 4000, 0));
        // 3999 impressions → 999.75 → floored to 999 (never over-charge).
        self::assertSame(999, SpendCalculator::compute(PricingModel::Cpm, 250, 3999, 0));
    }

    public function testCpc(): void
    {
        self::assertSame(1500, SpendCalculator::compute(PricingModel::Cpc, 50, 0, 30));
    }

    public function testFlat(): void
    {
        self::assertSame(9999, SpendCalculator::compute(PricingModel::Flat, 9999, 12345, 67));
    }
}
