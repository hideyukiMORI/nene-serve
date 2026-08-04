<?php

declare(strict_types=1);

namespace NeneServe\Tests\Runtime;

use Nene2\Middleware\InMemoryRateLimitStorage;
use Nene2\Middleware\RateLimitStorageInterface;
use NeneServe\Http\RateLimit\PdoRateLimitStorage;
use NeneServe\Http\RateLimit\RateLimitStoreMode;
use NeneServe\Http\RuntimeContainerFactory;
use PHPUnit\Framework\TestCase;

/**
 * #199 was a wiring defect: the container bound the per-process store
 * unconditionally, so the throttle middleware was mounted and configured yet
 * could never deny. The behaviour tests cannot see that — only the binding can.
 */
final class RateLimitStorageWiringTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER[RateLimitStoreMode::ENV_KEY], $_ENV[RateLimitStoreMode::ENV_KEY]);
    }

    public function testTheRuntimeBindsTheSharedStoreByDefault(): void
    {
        $storage = $this->resolveStorage();

        self::assertNotInstanceOf(InMemoryRateLimitStorage::class, $storage, 'The store #199 replaced must not come back.');
        self::assertInstanceOf(PdoRateLimitStorage::class, $storage);
    }

    public function testANonProductionInstallCanStillOptIntoTheInMemoryStore(): void
    {
        $_SERVER[RateLimitStoreMode::ENV_KEY] = 'memory';

        self::assertInstanceOf(InMemoryRateLimitStorage::class, $this->resolveStorage());
    }

    private function resolveStorage(): RateLimitStorageInterface
    {
        $storage = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create()->get(RateLimitStorageInterface::class);

        self::assertInstanceOf(RateLimitStorageInterface::class, $storage);

        return $storage;
    }
}
