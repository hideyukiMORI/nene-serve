<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http\RateLimit;

use Nene2\Config\AppEnvironment;
use NeneServe\Http\RateLimit\RateLimitStorageException;
use NeneServe\Http\RateLimit\RateLimitStoreMode;
use PHPUnit\Framework\TestCase;

final class RateLimitStoreModeTest extends TestCase
{
    public function testUnsetSelectsTheSharedDatabaseStore(): void
    {
        self::assertSame(RateLimitStoreMode::Database, RateLimitStoreMode::resolve('', AppEnvironment::Production));
        self::assertSame(RateLimitStoreMode::Database, RateLimitStoreMode::resolve('  ', AppEnvironment::Local));
    }

    public function testProductionRefusesTheStoreThatCannotEnforceALimit(): void
    {
        $this->expectException(RateLimitStorageException::class);
        $this->expectExceptionMessageMatches('/cannot enforce a rate limit in production/');

        RateLimitStoreMode::resolve('memory', AppEnvironment::Production);
    }

    public function testNonProductionMayOptIntoTheInMemoryStore(): void
    {
        self::assertSame(RateLimitStoreMode::Memory, RateLimitStoreMode::resolve('memory', AppEnvironment::Local));
        self::assertSame(RateLimitStoreMode::Memory, RateLimitStoreMode::resolve('MEMORY', AppEnvironment::Test));
        self::assertSame(RateLimitStoreMode::Memory, RateLimitStoreMode::resolve(' memory ', AppEnvironment::Test));
    }

    public function testTheDatabaseStoreIsAcceptedEverywhere(): void
    {
        self::assertSame(RateLimitStoreMode::Database, RateLimitStoreMode::resolve('database', AppEnvironment::Production));
        self::assertSame(RateLimitStoreMode::Database, RateLimitStoreMode::resolve('database', AppEnvironment::Local));
    }

    public function testAnUnrecognisedValueIsAConfigurationErrorRatherThanASilentFallback(): void
    {
        $this->expectException(RateLimitStorageException::class);
        $this->expectExceptionMessageMatches('/must be one of/');

        RateLimitStoreMode::resolve('redis', AppEnvironment::Local);
    }

    public function testATypoDoesNotDegradeProductionToAnInertLimiter(): void
    {
        // "memroy" must not resolve to anything at all — least of all a store
        // production refuses when spelled correctly.
        $this->expectException(RateLimitStorageException::class);

        RateLimitStoreMode::resolve('memroy', AppEnvironment::Production);
    }
}
