<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement;

use NeneServe\Measurement\InMemoryEventStore;
use NeneServe\Measurement\UseCase\RecordClickUseCase;
use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use NeneServe\Serving\Token\FileTokenStore;
use PHPUnit\Framework\TestCase;

/**
 * Click recording: the single-use token counts a click at most once, opt-out
 * placements record nothing, yet the redirect (an essential function) always
 * resolves so the user still reaches the destination.
 */
final class RecordClickUseCaseTest extends TestCase
{
    private const ORG = 'org-1';
    private const PLACEMENT = 'plc-1';
    private const CREATIVE = 'cr-1';
    private const DEST = 'https://advertiser.example.com/landing';

    private string $tokenPath;
    private FileTokenStore $tokens;
    private InMemoryEventStore $events;

    protected function setUp(): void
    {
        $this->tokenPath = sys_get_temp_dir() . '/serve-click-' . bin2hex(random_bytes(6)) . '.json';
        $this->tokens = new FileTokenStore($this->tokenPath);
        $this->events = new InMemoryEventStore();
    }

    protected function tearDown(): void
    {
        @unlink($this->tokenPath);
    }

    private function useCase(bool $measurementEnabled = true): RecordClickUseCase
    {
        $placement = new Placement(self::PLACEMENT, self::ORG, 'pk_home', [], 'active', null, $measurementEnabled);

        return new RecordClickUseCase($this->tokens, $this->events, new InMemoryPlacementRepository([$placement]));
    }

    private function issueToken(): string
    {
        return $this->tokens->issueClickToken(self::ORG, self::PLACEMENT, self::CREATIVE, self::DEST, 900);
    }

    private function clickCount(): int
    {
        $total = 0;
        foreach ($this->events->dailyMetrics(self::ORG, '2000-01-01', '2100-01-01') as $row) {
            $total += $row->clicks;
        }

        return $total;
    }

    public function testRecordsAClickAndReturnsTheRedirect(): void
    {
        $redirect = $this->useCase()->execute($this->issueToken());

        self::assertNotNull($redirect);
        self::assertSame(self::DEST, $redirect->destinationUrl);
        self::assertSame(1, $this->clickCount());
    }

    public function testTokenIsSingleUse(): void
    {
        $token = $this->issueToken();
        $useCase = $this->useCase();

        self::assertNotNull($useCase->execute($token));
        // Second consume of the same token must not resolve nor double-count.
        self::assertNull($useCase->execute($token));
        self::assertSame(1, $this->clickCount());
    }

    public function testUnknownTokenResolvesToNullAndRecordsNothing(): void
    {
        self::assertNull($this->useCase()->execute('not-a-real-token'));
        self::assertSame(0, $this->clickCount());
    }

    public function testOptedOutPlacementStillRedirectsButRecordsNoClick(): void
    {
        $redirect = $this->useCase(measurementEnabled: false)->execute($this->issueToken());

        self::assertNotNull($redirect);
        self::assertSame(self::DEST, $redirect->destinationUrl);
        self::assertSame(0, $this->clickCount());
    }
}
