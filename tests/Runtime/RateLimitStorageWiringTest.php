<?php

declare(strict_types=1);

namespace NeneServe\Tests\Runtime;

use Nene2\Middleware\InMemoryRateLimitStorage;
use Nene2\Middleware\RateLimitStorageInterface;
use NeneServe\Http\RateLimit\PdoRateLimitStorage;
use NeneServe\Http\RateLimit\RateLimitStoreMode;
use NeneServe\Http\RuntimeContainerFactory;
use NeneServe\Serving\Frequency\FileFrequencyCapStore;
use NeneServe\Serving\Frequency\FrequencyCapStoreInterface;
use NeneServe\Serving\Frequency\PdoFrequencyCapStore;
use NeneServe\Serving\PublicStoreMode;
use NeneServe\Serving\Token\FileTokenStore;
use NeneServe\Serving\Token\PdoTokenStore;
use NeneServe\Serving\Token\TokenStoreInterface;
use PHPUnit\Framework\TestCase;

/**
 * #199 was a wiring defect: the container bound the per-process store
 * unconditionally, so the throttle middleware was mounted and configured yet
 * could never deny. The behaviour tests cannot see that — only the binding can.
 *
 * #207 extends the same guard to the token and frequency stores, whose
 * single-host versions fail in a quieter way: they work until a second host
 * appears, then drop clicks with nothing in the logs.
 */
final class RateLimitStorageWiringTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $_SERVER[RateLimitStoreMode::ENV_KEY],
            $_ENV[RateLimitStoreMode::ENV_KEY],
            $_SERVER[PublicStoreMode::ENV_KEY],
            $_ENV[PublicStoreMode::ENV_KEY],
        );
    }

    public function testTheRuntimeBindsTheSharedPublicStoresByDefault(): void
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        $tokens = $container->get(TokenStoreInterface::class);
        $frequency = $container->get(FrequencyCapStoreInterface::class);

        self::assertNotInstanceOf(FileTokenStore::class, $tokens, 'Single-host token storage must not come back.');
        self::assertNotInstanceOf(FileFrequencyCapStore::class, $frequency);
        self::assertInstanceOf(PdoTokenStore::class, $tokens);
        self::assertInstanceOf(PdoFrequencyCapStore::class, $frequency);
    }

    public function testANonProductionInstallCanStillOptIntoTheFileStores(): void
    {
        $_SERVER[PublicStoreMode::ENV_KEY] = 'file';

        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        self::assertInstanceOf(FileTokenStore::class, $container->get(TokenStoreInterface::class));
        self::assertInstanceOf(FileFrequencyCapStore::class, $container->get(FrequencyCapStoreInterface::class));
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
