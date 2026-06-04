<?php

declare(strict_types=1);

/**
 * composer locales:check — six-locale key-parity check (ADR 0011, i18n.md).
 *
 * Full check: flattens nested keys to dotted paths, asserts every locale matches
 * the canonical en.json key set, and verifies _one/_other pluralization pairs.
 * Run in CI (.github/workflows/ci.yml).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use NeneServe\I18n\LocaleCatalogs;

$result = LocaleCatalogs::check(__DIR__ . '/../locales');

foreach ($result->keyCounts as $code => $count) {
    printf("  %-8s %d keys\n", $code, $count);
}

if (!$result->ok) {
    fwrite(STDERR, "\n✗ locales:check FAILED\n");
    foreach ($result->errors as $error) {
        fwrite(STDERR, "    {$error}\n");
    }
    exit(1);
}

printf("\n✓ locales:check OK — %d locales in parity\n", count(LocaleCatalogs::LOCALES));
exit(0);
