<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement\Metrics;

use NeneServe\Measurement\Metrics\MetricsRange;
use NeneServe\Tests\Support\FixedClock;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `from`/`to` metrics window parsing. Defaults to the trailing 30-day window
 * (today and today-29), where "today" comes from the injected clock — asserted
 * deterministically against a {@see FixedClock}. Any malformed date rejects the
 * whole window.
 */
final class MetricsRangeTest extends TestCase
{
    /** FixedClock's default instant is 2026-07-06T09:00:00+00:00. */
    private const TODAY = '2026-07-06';
    private const TODAY_MINUS_29 = '2026-06-07';

    private FixedClock $clock;

    protected function setUp(): void
    {
        $this->clock = new FixedClock();
    }

    /** @param array<string, mixed> $query */
    private function request(array $query): ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest('GET', '/admin/metrics')->withQueryParams($query);
    }

    public function testDefaultsToTrailing30DayWindow(): void
    {
        $range = MetricsRange::fromRequest($this->request([]), $this->clock);

        self::assertSame([self::TODAY_MINUS_29, self::TODAY], $range);
    }

    public function testUsesExplicitFromAndTo(): void
    {
        $range = MetricsRange::fromRequest($this->request(['from' => '2026-01-01', 'to' => '2026-01-31']), $this->clock);

        self::assertSame(['2026-01-01', '2026-01-31'], $range);
    }

    public function testFromDefaultsWhenOnlyToGiven(): void
    {
        $range = MetricsRange::fromRequest($this->request(['to' => '2026-01-31']), $this->clock);

        self::assertSame([self::TODAY_MINUS_29, '2026-01-31'], $range);
    }

    public function testToDefaultsToTodayWhenOnlyFromGiven(): void
    {
        $range = MetricsRange::fromRequest($this->request(['from' => '2026-01-01']), $this->clock);

        self::assertSame(['2026-01-01', self::TODAY], $range);
    }

    /** @return iterable<string, array{string}> */
    public static function malformedDates(): iterable
    {
        yield 'slashes' => ['2026/06/06'];
        yield 'unpadded' => ['2026-6-6'];
        yield 'no separators' => ['20260606'];
        yield 'word' => ['yesterday'];
        yield 'empty' => [''];
        yield 'too long' => ['2026-06-061'];
        yield 'iso datetime' => ['2026-06-06T00:00:00'];
    }

    #[DataProvider('malformedDates')]
    public function testRejectsMalformedFrom(string $from): void
    {
        self::assertNull(MetricsRange::fromRequest($this->request(['from' => $from]), $this->clock));
    }

    #[DataProvider('malformedDates')]
    public function testRejectsMalformedTo(string $to): void
    {
        self::assertNull(MetricsRange::fromRequest($this->request(['to' => $to]), $this->clock));
    }

    public function testNonStringParamsFallBackToDefaults(): void
    {
        // An array-valued param is not a string, so it is ignored (defaults used).
        $range = MetricsRange::fromRequest($this->request(['from' => ['x'], 'to' => ['y']]), $this->clock);

        self::assertSame([self::TODAY_MINUS_29, self::TODAY], $range);
    }
}
