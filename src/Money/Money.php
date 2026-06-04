<?php

declare(strict_types=1);

namespace NeneServe\Money;

use InvalidArgumentException;

/**
 * Net money as an integer minimum currency unit (billing-and-accounting §4).
 *
 * **Hard rules (binding):** integer cents only — **no float, no DECIMAL**;
 * **JPY only** in Phase 3 (¥1 = 1 unit); amounts are **net of tax** — Money
 * carries no tax component and Serve never computes tax (§2). Non-negative.
 */
final class Money
{
    public const CURRENCY = 'JPY';

    private function __construct(
        public readonly int $cents,
    ) {
    }

    public static function fromCents(int $cents, string $currency = self::CURRENCY): self
    {
        if ($currency !== self::CURRENCY) {
            throw new InvalidArgumentException('Phase 3 supports JPY only (adding a currency is an ADR-level change).');
        }
        if ($cents < 0) {
            throw new InvalidArgumentException('Money must be non-negative.');
        }

        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    /** Multiply a unit rate by a non-negative count of billable units. */
    public function timesUnits(int $units): self
    {
        if ($units < 0) {
            throw new InvalidArgumentException('Units must be non-negative.');
        }

        return new self($this->cents * $units);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->cents >= $other->cents;
    }

    /** @return array{cents: int, currency: string} */
    public function toArray(): array
    {
        return ['cents' => $this->cents, 'currency' => self::CURRENCY];
    }
}
