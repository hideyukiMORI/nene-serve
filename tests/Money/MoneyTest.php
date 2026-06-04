<?php

declare(strict_types=1);

namespace NeneServe\Tests\Money;

use InvalidArgumentException;
use NeneServe\Money\Money;
use PHPUnit\Framework\TestCase;

/**
 * Money invariants (billing-and-accounting §4): integer cents, JPY only,
 * non-negative, no tax — and no float ever leaks in.
 */
final class MoneyTest extends TestCase
{
    public function testStoresIntegerCentsAsJpy(): void
    {
        $m = Money::fromCents(1500);
        self::assertSame(1500, $m->cents);
        self::assertSame(['cents' => 1500, 'currency' => 'JPY'], $m->toArray());
    }

    public function testRejectsNonJpyCurrency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(100, 'USD');
    }

    public function testRejectsNegativeAmounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(-1);
    }

    public function testArithmeticStaysInteger(): void
    {
        // 250 (CPM rate per 1000) over 4000 impressions → 4 units → 1000.
        $rate = Money::fromCents(250);
        self::assertSame(1000, $rate->timesUnits(4)->cents);
        self::assertSame(1250, $rate->add(Money::fromCents(1000))->cents);
        self::assertIsInt($rate->timesUnits(4)->cents);
    }

    public function testComparisons(): void
    {
        self::assertTrue(Money::fromCents(1000)->isGreaterThan(Money::fromCents(999)));
        self::assertTrue(Money::fromCents(1000)->isGreaterThanOrEqual(Money::fromCents(1000)));
        self::assertFalse(Money::fromCents(999)->isGreaterThan(Money::fromCents(1000)));
    }
}
