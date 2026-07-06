<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use NeneServe\Serving\Token\FileTokenStore;
use NeneServe\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/**
 * The file-backed store must persist tokens across instances (i.e. across
 * separate HTTP requests) while keeping click tokens single-use and expiring.
 */
final class FileTokenStoreTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/nene-tokens-' . bin2hex(random_bytes(6)) . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function testClickTokenPersistsAcrossInstancesAndIsSingleUse(): void
    {
        $writer = new FileTokenStore($this->path);
        $token = $writer->issueClickToken('org-1', 'plc-1', 'cr-1', 'https://x.test/landing', 900);

        // A fresh instance (new request) still resolves the token.
        $reader = new FileTokenStore($this->path);
        $redirect = $reader->consumeClickToken($token);
        self::assertNotNull($redirect);
        self::assertSame('https://x.test/landing', $redirect->destinationUrl);

        // Single use across instances.
        self::assertNull(new FileTokenStore($this->path)->consumeClickToken($token));
    }

    public function testExpiredClickTokenIsRejected(): void
    {
        $store = new FileTokenStore($this->path);
        $token = $store->issueClickToken('org-1', 'plc-1', 'cr-1', 'https://x.test/landing', -1);

        self::assertNull($store->consumeClickToken($token));
    }

    public function testClickTokenExpiryIsDeterministicWithFixedClock(): void
    {
        // Issue a 15-minute click token at a fixed instant.
        $issued = new FileTokenStore($this->path, new FixedClock('2026-07-06T09:00:00+00:00'));
        $token = $issued->issueClickToken('org-1', 'plc-1', 'cr-1', 'https://x.test/landing', 900);

        // 14 minutes later the token still resolves (within the window).
        $withinWindow = new FileTokenStore($this->path, new FixedClock('2026-07-06T09:14:00+00:00'));
        self::assertNotNull($withinWindow->consumeClickToken($token));

        // Re-issue and advance past the window: the token is expired, no wall-clock
        // sleep required — the fixed clock makes the boundary exact.
        $reissued = new FileTokenStore($this->path, new FixedClock('2026-07-06T09:00:00+00:00'));
        $token2 = $reissued->issueClickToken('org-1', 'plc-1', 'cr-1', 'https://x.test/landing', 900);

        $afterWindow = new FileTokenStore($this->path, new FixedClock('2026-07-06T09:15:01+00:00'));
        self::assertNull($afterWindow->consumeClickToken($token2));
    }

    public function testImpressionIsIdempotentAcrossInstances(): void
    {
        $token = new FileTokenStore($this->path)->issueImpressionToken('org-1', 'plc-1', 'cr-1');

        $first = new FileTokenStore($this->path)->recordImpression($token);
        $second = new FileTokenStore($this->path)->recordImpression($token);

        self::assertNotNull($first);
        self::assertFalse($first->alreadyRecorded);
        self::assertNotNull($second);
        self::assertTrue($second->alreadyRecorded);
    }
}
