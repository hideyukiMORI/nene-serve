<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement;

use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Measurement\UseCase\RecordConversionUseCase;
use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Conversion recording (ADR 0009): attributed to a placement's delivery, append-
 * only, opt-out honoured, never a Contact submission. An unknown placement is a
 * hard 404 (no silent swallow), an opted-out one is a silent no-op.
 */
final class RecordConversionUseCaseTest extends TestCase
{
    private const ORG = 'org-1';
    private const KEY = 'pk_home';

    private InMemoryEventStore $events;

    protected function setUp(): void
    {
        $this->events = new InMemoryEventStore();
    }

    private function useCase(bool $measurementEnabled = true): RecordConversionUseCase
    {
        $placement = new Placement('plc-1', self::ORG, self::KEY, [], 'active', null, $measurementEnabled);

        return new RecordConversionUseCase(new InMemoryPlacementRepository([$placement]), $this->events);
    }

    private function conversionCount(): int
    {
        $total = 0;
        foreach ($this->events->dailyConversions(self::ORG, '2000-01-01', '2100-01-01') as $row) {
            $total += $row['conversions'];
        }

        return $total;
    }

    public function testRecordsAConversion(): void
    {
        $this->useCase()->execute(self::KEY, 'cr-1', 'JP');

        self::assertSame(1, $this->conversionCount());
    }

    public function testUnknownPlacementThrows(): void
    {
        $this->expectException(PlacementNotFoundException::class);
        $this->useCase()->execute('pk_does_not_exist');
    }

    public function testOptedOutPlacementRecordsNothing(): void
    {
        $this->useCase(measurementEnabled: false)->execute(self::KEY);

        self::assertSame(0, $this->conversionCount());
    }

    public function testWorksWithoutOptionalCreativeOrCountry(): void
    {
        $this->useCase()->execute(self::KEY);

        self::assertSame(1, $this->conversionCount());
    }
}
