<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Support\Database;
use NeneServe\Support\KernelFactory;
use PHPUnit\Framework\TestCase;

/**
 * The live entry point builds its kernel through {@see KernelFactory}. The
 * development path (no DB_HOST) must boot the file/in-memory defaults; the
 * database path wires the PDO repositories and is exercised against a live MySQL
 * when DB_HOST is present (skipped otherwise so the default suite needs no DB).
 */
final class KernelFactoryTest extends TestCase
{
    private string $storageDir = '';

    protected function setUp(): void
    {
        $dir = sys_get_temp_dir() . '/nene-factory-' . bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);
        $this->storageDir = $dir;
    }

    protected function tearDown(): void
    {
        foreach (glob($this->storageDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->storageDir);
    }

    public function testDevelopmentModeBootsWithoutDatabase(): void
    {
        $kernel = KernelFactory::create($this->storageDir, []);
        self::assertInstanceOf(Kernel::class, $kernel);

        $response = $kernel->handle(new Request('GET', '/health'));
        self::assertSame(200, $response->status);
    }

    public function testDevelopmentModeServesFromSeedFixtures(): void
    {
        $kernel = KernelFactory::create($this->storageDir, []);

        $response = $kernel->handle(new Request('GET', '/public/placements/pk_acme_home/serve'));
        self::assertSame(200, $response->status);

        // Token store is file-backed in the live boot so beacons survive the next request.
        self::assertFileExists($this->storageDir . '/tokens.json');
    }

    public function testSiblingClientsAreOptionalAndDoNotBlockBoot(): void
    {
        // Configured sibling env should construct the HTTP clients without contacting them.
        $kernel = KernelFactory::create($this->storageDir, [
            'NENE_INVOICE_API_BASE_URL' => 'https://invoice.example',
            'NENE_INVOICE_SERVICE_TOKEN' => 'tok',
            'NENE_RECORDS_API_BASE_URL' => 'https://records.example',
            'NENE_RECORDS_SERVICE_TOKEN' => 'tok',
            'NENE_DEAL_API_BASE_URL' => 'https://deal.example',
            'NENE_DEAL_SERVICE_TOKEN' => 'tok',
        ]);

        self::assertSame(200, $kernel->handle(new Request('GET', '/health'))->status);
    }

    public function testDatabaseModeWiresPdoRepositoriesAgainstLiveMysql(): void
    {
        if (getenv('DB_HOST') === false) {
            self::markTestSkipped('Set DB_HOST (with migrations applied) to run the PDO integration check.');
        }

        $kernel = KernelFactory::database(Database::fromEnv(), $this->storageDir, static fn (string $k): ?string => null);

        // /health never touches the DB.
        self::assertSame(200, $kernel->handle(new Request('GET', '/health'))->status);

        // Serving an unknown key executes a real SELECT through PdoPlacementRepository:
        // a 404 (not a 500) proves the wiring + schema are sound.
        $response = $kernel->handle(new Request('GET', '/public/placements/pk_no_such_key/serve'));
        self::assertSame(404, $response->status);
    }
}
