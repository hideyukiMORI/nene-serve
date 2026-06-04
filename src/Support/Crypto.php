<?php

declare(strict_types=1);

namespace NeneServe\Support;

use SodiumException;

/**
 * Authenticated encryption at rest (libsodium secretbox) for operational
 * secrets stored in governed tables — e.g. the SMTP password (api-security §6,
 * ADR 0022: secrets never in plaintext). The key comes from the environment
 * only (`APP_ENCRYPTION_KEY`, base64 of 32 bytes); it is never committed and
 * never stored in the database.
 *
 * Ciphertext layout (base64): nonce (24 bytes) || secretbox(ciphertext+MAC).
 * Decryption verifies the MAC, so tampering is detected (throws).
 */
final class Crypto
{
    private const ENV_KEY = 'APP_ENCRYPTION_KEY';

    public static function isConfigured(): bool
    {
        return self::rawKey() !== null;
    }

    public function encrypt(string $plaintext): string
    {
        $key = self::requireKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);

        return base64_encode($nonce . $cipher);
    }

    /**
     * @throws CryptoException when the key is missing, the input is malformed,
     *                         or the MAC fails (tampering / wrong key).
     */
    public function decrypt(string $encoded): string
    {
        $key = self::requireKey();
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new CryptoException('Malformed ciphertext.');
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        try {
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
        } catch (SodiumException $e) {
            throw new CryptoException('Decryption failed.', 0, $e);
        }
        if ($plain === false) {
            throw new CryptoException('Decryption failed (authentication mismatch).');
        }

        return $plain;
    }

    private static function rawKey(): ?string
    {
        $value = getenv(self::ENV_KEY);
        if (!is_string($value) || $value === '') {
            return null;
        }
        $key = base64_decode($value, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return null;
        }

        return $key;
    }

    private static function requireKey(): string
    {
        $key = self::rawKey();
        if ($key === null) {
            throw new CryptoException(
                self::ENV_KEY . ' must be set to a base64-encoded 32-byte key.',
            );
        }

        return $key;
    }
}
