<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use NeneServe\Support\Crypto;
use NeneServe\Support\CryptoException;
use PHPUnit\Framework\TestCase;

/** At-rest encryption (libsodium): round-trip, non-determinism, tamper detection, key handling. */
final class CryptoTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('APP_ENCRYPTION_KEY=' . base64_encode(str_repeat('k', 32)));
    }

    protected function tearDown(): void
    {
        putenv('APP_ENCRYPTION_KEY');
    }

    public function testRoundTrip(): void
    {
        $crypto = new Crypto();
        self::assertTrue(Crypto::isConfigured());
        $cipher = $crypto->encrypt('smtp-secret');
        self::assertNotSame('smtp-secret', $cipher);
        self::assertSame('smtp-secret', $crypto->decrypt($cipher));
    }

    public function testEncryptionIsNonDeterministic(): void
    {
        $crypto = new Crypto();
        self::assertNotSame($crypto->encrypt('same'), $crypto->encrypt('same'));
    }

    public function testTamperingIsDetected(): void
    {
        $crypto = new Crypto();
        $cipher = $crypto->encrypt('value');

        $this->expectException(CryptoException::class);
        $crypto->decrypt(substr($cipher, 0, -3) . 'AAA');
    }

    public function testMissingKeyIsRefused(): void
    {
        putenv('APP_ENCRYPTION_KEY');
        self::assertFalse(Crypto::isConfigured());

        $this->expectException(CryptoException::class);
        (new Crypto())->encrypt('x');
    }
}
