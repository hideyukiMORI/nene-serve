<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use NeneServe\Serving\Scan\ClamAvScanner;
use NeneServe\Serving\ScanStatus;
use PHPUnit\Framework\TestCase;

/**
 * The clamd client is fail-closed by contract; a live-engine check (clean vs
 * EICAR) is gated on CLAMAV_TEST so CI needs no scanner.
 */
final class ClamAvScannerTest extends TestCase
{
    public function testUnreachableScannerFailsClosed(): void
    {
        // Nothing listens here → must NOT report Clean (would let malware through).
        $scanner = new ClamAvScanner('127.0.0.1', 1, 1);
        self::assertSame(ScanStatus::Pending, $scanner->scan('bundle-1', '<html></html>'));
    }

    public function testDetectsCleanAndEicarAgainstLiveClamd(): void
    {
        if (getenv('CLAMAV_TEST') !== '1') {
            self::markTestSkipped('Set CLAMAV_TEST=1 (with clamd on 3310) to run the live scan check.');
        }

        $host = getenv('CLAMAV_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('CLAMAV_PORT') ?: '3310');
        $scanner = new ClamAvScanner((string) $host, $port, 30);

        self::assertSame(ScanStatus::Clean, $scanner->scan('clean', '<html><body>hello</body></html>'));

        $eicar = 'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
        self::assertSame(ScanStatus::Flagged, $scanner->scan('eicar', $eicar));
    }
}
