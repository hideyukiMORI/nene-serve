<?php

declare(strict_types=1);

namespace NeneServe\Serving\Scan;

use NeneServe\Serving\ScanStatus;

/**
 * Placeholder scanner for boot/tests until a real engine is wired. Flags bundles
 * containing the EICAR test marker (or any configured token); everything else is
 * reported `clean`. NOT a real malware scanner.
 */
final class StubBundleScanner implements BundleScannerInterface
{
    private const EICAR = 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE';

    public function scan(string $bundleId, string $htmlEntry): ScanStatus
    {
        return str_contains($htmlEntry, self::EICAR) ? ScanStatus::Flagged : ScanStatus::Clean;
    }
}
