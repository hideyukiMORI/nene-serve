<?php

declare(strict_types=1);

namespace NeneServe\Serving\Scan;

use NeneServe\Serving\ScanStatus;

/**
 * Real malware scanner backed by clamd (ClamAV) over TCP using the INSTREAM
 * command — dependency-free (fsockopen), consistent with the rest of the runtime.
 *
 * Fail-closed: any connection/protocol error returns {@see ScanStatus::Pending}
 * (NOT Clean), so a bundle can never reach review/serve while the scanner is
 * unavailable (ADR 0021 §4 — only `clean` proceeds).
 */
final class ClamAvScanner implements BundleScannerInterface
{
    private const CHUNK = 8192;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 3310,
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    public function scan(string $bundleId, string $htmlEntry): ScanStatus
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, (float) $this->timeoutSeconds);
        if ($socket === false) {
            return ScanStatus::Pending; // scanner unreachable → fail closed
        }
        stream_set_timeout($socket, $this->timeoutSeconds);

        try {
            if (@fwrite($socket, "zINSTREAM\0") === false) {
                return ScanStatus::Pending;
            }
            // The scanned payload is the bundle's entry document (ADR 0021 §4).
            foreach (str_split($htmlEntry === '' ? ' ' : $htmlEntry, self::CHUNK) as $chunk) {
                if (@fwrite($socket, pack('N', strlen($chunk)) . $chunk) === false) {
                    return ScanStatus::Pending;
                }
            }
            @fwrite($socket, pack('N', 0)); // zero-length chunk = end of stream

            $response = '';
            while (!feof($socket)) {
                $part = fgets($socket);
                if ($part === false) {
                    break;
                }
                $response .= $part;
            }
        } finally {
            @fclose($socket);
        }

        if (str_contains($response, 'FOUND')) {
            return ScanStatus::Flagged;
        }
        if (str_contains($response, 'OK')) {
            return ScanStatus::Clean;
        }

        return ScanStatus::Pending; // unexpected reply → fail closed
    }
}
