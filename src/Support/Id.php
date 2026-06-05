<?php

declare(strict_types=1);

namespace NeneServe\Support;

/**
 * Random opaque identifiers for governed entities and append-only event rows.
 * One place owns the entropy and the `prefix-hex` format. (Distinct from
 * {@see \Nene2\Http\SecureTokenHelper}, which is for security tokens that are
 * hashed before storage.)
 */
final class Id
{
    /**
     * A prefixed entity id, e.g. `Id::generate('adv')` → `adv-1a2b…`.
     *
     * @param int<1, max> $bytes
     */
    public static function generate(string $prefix, int $bytes = 8): string
    {
        return $prefix . '-' . self::random($bytes);
    }

    /**
     * A bare random hex id (for event rows that carry no prefix).
     *
     * @param int<1, max> $bytes
     */
    public static function random(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }
}
