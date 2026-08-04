<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use Nene2\Config\AppEnvironment;
use NeneServe\Serving\PublicStoreException;
use NeneServe\Serving\PublicStoreMode;
use PHPUnit\Framework\TestCase;

final class PublicStoreModeTest extends TestCase
{
    public function testUnsetSelectsTheSharedDatabaseStore(): void
    {
        self::assertSame(PublicStoreMode::Database, PublicStoreMode::resolve('', AppEnvironment::Production));
        self::assertSame(PublicStoreMode::Database, PublicStoreMode::resolve('  ', AppEnvironment::Local));
    }

    public function testProductionRefusesSingleHostFileStorage(): void
    {
        $this->expectException(PublicStoreException::class);
        $this->expectExceptionMessageMatches('/only one host can read/');

        PublicStoreMode::resolve('file', AppEnvironment::Production);
    }

    public function testNonProductionMayOptIntoTheFileStores(): void
    {
        self::assertSame(PublicStoreMode::File, PublicStoreMode::resolve('file', AppEnvironment::Local));
        self::assertSame(PublicStoreMode::File, PublicStoreMode::resolve('FILE', AppEnvironment::Test));
        self::assertSame(PublicStoreMode::File, PublicStoreMode::resolve(' file ', AppEnvironment::Test));
    }

    public function testTheDatabaseStoreIsAcceptedEverywhere(): void
    {
        self::assertSame(PublicStoreMode::Database, PublicStoreMode::resolve('database', AppEnvironment::Production));
        self::assertSame(PublicStoreMode::Database, PublicStoreMode::resolve('database', AppEnvironment::Local));
    }

    public function testAnUnrecognisedValueIsAConfigurationErrorRatherThanASilentFallback(): void
    {
        $this->expectException(PublicStoreException::class);
        $this->expectExceptionMessageMatches('/must be one of/');

        PublicStoreMode::resolve('redis', AppEnvironment::Local);
    }

    public function testATypoDoesNotDegradeProductionToSingleHostStorage(): void
    {
        $this->expectException(PublicStoreException::class);

        PublicStoreMode::resolve('fiel', AppEnvironment::Production);
    }
}
