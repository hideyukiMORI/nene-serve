<?php

declare(strict_types=1);

namespace NeneServe\I18n;

/**
 * Six-locale catalog parity check (ADR 0011, i18n.md). Flattens nested JSON to
 * dotted keys and asserts every locale carries the exact key set of the
 * authority `ja` catalog, plus ICU-lite pluralization consistency
 * (`*_one` ⇔ `*_other`). The `_meta` block is ignored.
 *
 * Authority is `ja` (ADR 0011; Frontend Standard 04, I18N-8): keys are authored
 * in Japanese first and every other catalog — including `en` — mirrors that key
 * set. `en` remains the default/fallback locale at runtime; only the key-set
 * authority lives here.
 */
final class LocaleCatalogs
{
    public const LOCALES = ['en', 'ja', 'zh-Hans', 'ko', 'de', 'es'];
    public const CANONICAL = 'ja';

    /**
     * Flattens a (possibly nested) catalog to a sorted list of dotted keys,
     * excluding `_meta`.
     *
     * @param array<string, mixed> $data
     * @return list<string>
     */
    public static function flatten(array $data, string $prefix = ''): array
    {
        $keys = [];
        foreach ($data as $key => $value) {
            if ($prefix === '' && $key === '_meta') {
                continue;
            }
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $keys = array_merge($keys, self::flatten($value, $path));
            } else {
                $keys[] = $path;
            }
        }
        sort($keys);

        return $keys;
    }

    /**
     * Pluralization keys must come in `_one` / `_other` pairs (i18n.md §3).
     *
     * @param list<string> $keys
     * @return list<string> human-readable problems
     */
    public static function pluralProblems(array $keys): array
    {
        $set = array_fill_keys($keys, true);
        $problems = [];
        foreach ($keys as $key) {
            if (str_ends_with($key, '_one') && !isset($set[substr($key, 0, -4) . '_other'])) {
                $problems[] = "{$key} has no matching _other";
            }
            if (str_ends_with($key, '_other') && !isset($set[substr($key, 0, -6) . '_one'])) {
                $problems[] = "{$key} has no matching _one";
            }
        }

        return $problems;
    }

    public static function check(string $dir): LocaleCheckResult
    {
        $errors = [];
        $counts = [];

        $canonical = self::load($dir, self::CANONICAL, $errors);
        $canonicalKeys = self::flatten($canonical);

        foreach (self::pluralProblems($canonicalKeys) as $problem) {
            $errors[] = self::CANONICAL . ": {$problem}";
        }

        // Counts are reported in declared LOCALES order, independent of which
        // locale is the authority.
        foreach (self::LOCALES as $code) {
            if ($code === self::CANONICAL) {
                $counts[$code] = count($canonicalKeys);

                continue;
            }
            $keys = self::flatten(self::load($dir, $code, $errors));
            $counts[$code] = count($keys);

            foreach (array_diff($canonicalKeys, $keys) as $missing) {
                $errors[] = "{$code}: missing key '{$missing}'";
            }
            foreach (array_diff($keys, $canonicalKeys) as $extra) {
                $errors[] = "{$code}: extra key '{$extra}' not in " . self::CANONICAL;
            }
        }

        return new LocaleCheckResult($errors === [], $errors, $counts);
    }

    /**
     * @param list<string> $errors
     * @return array<string, mixed>
     */
    private static function load(string $dir, string $code, array &$errors): array
    {
        $path = $dir . '/' . $code . '.json';
        if (!is_file($path)) {
            $errors[] = "{$code}: missing catalog {$code}.json";

            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            $errors[] = "{$code}: invalid JSON in {$code}.json";

            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
