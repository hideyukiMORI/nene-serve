<?php

declare(strict_types=1);

namespace NeneServe\Serving\Scan;

use NeneServe\Serving\ScanStatus;

/**
 * Malware scanner for HTML5 bundles (ADR 0021 §4). Production wires a real
 * engine (e.g. ClamAV) behind this interface; only a `clean` result may proceed.
 */
interface BundleScannerInterface
{
    public function scan(string $bundleId, string $htmlEntry): ScanStatus;
}
