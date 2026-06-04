<?php

declare(strict_types=1);

/**
 * composer locales:check — six-locale key-parity check (ADR 0011).
 *
 * STUB (Phase 1-A, #10): validates top-level key parity of every catalog
 * against the canonical `en.json`, ignoring the `_meta` block. #15 promotes
 * this to a full check (nested keys, pluralization suffixes, CI gating).
 */

const LOCALE_DIR = __DIR__ . '/../locales';
const REQUIRED = ['en', 'ja', 'zh-Hans', 'ko', 'de', 'es']; // ADR 0011
const CANONICAL = 'en';

/** @return array<string, mixed> */
function loadCatalog(string $code): array
{
    $path = LOCALE_DIR . '/' . $code . '.json';
    if (!is_file($path)) {
        fwrite(STDERR, "✗ missing catalog: locales/{$code}.json\n");
        exit(1);
    }
    /** @var array<string, mixed>|null $data */
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        fwrite(STDERR, "✗ invalid JSON: locales/{$code}.json\n");
        exit(1);
    }
    unset($data['_meta']);

    return $data;
}

$canonicalKeys = array_keys(loadCatalog(CANONICAL));
sort($canonicalKeys);

$failed = false;
foreach (REQUIRED as $code) {
    $keys = array_keys(loadCatalog($code));
    sort($keys);

    $missing = array_diff($canonicalKeys, $keys);
    $extra = array_diff($keys, $canonicalKeys);

    if ($missing === [] && $extra === []) {
        printf("✓ %-8s %d keys\n", $code, count($keys));
        continue;
    }

    $failed = true;
    printf("✗ %-8s parity mismatch vs %s\n", $code, CANONICAL);
    if ($missing !== []) {
        printf("    missing: %s\n", implode(', ', $missing));
    }
    if ($extra !== []) {
        printf("    extra:   %s\n", implode(', ', $extra));
    }
}

if ($failed) {
    fwrite(STDERR, "\nlocales:check FAILED\n");
    exit(1);
}

printf("\nlocales:check OK — %d locales, %d keys each\n", count(REQUIRED), count($canonicalKeys));
exit(0);
