<?php

declare(strict_types=1);

namespace NeneServe\Http\Auth;

/**
 * Minimal, dependency-free HS256 JWT (RFC 7519) for admin sessions.
 *
 * Kept self-contained so the scaffold verifies tokens before a vetted library
 * (e.g. firebase/php-jwt) is installed; the production runtime SHOULD swap the
 * implementation behind this class. Signing secret comes from
 * `NENE_SERVE_JWT_SECRET` (api-security §6).
 */
final class Jwt
{
    private const ALG = 'HS256';

    public function __construct(
        private readonly string $secret,
    ) {
        if ($this->secret === '') {
            throw new JwtException('JWT secret is empty.');
        }
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function issue(array $claims, int $ttlSeconds = 3600): string
    {
        $now = time();
        $payload = $claims + ['iat' => $now, 'exp' => $now + $ttlSeconds];

        $header = self::b64encode((string) json_encode(['alg' => self::ALG, 'typ' => 'JWT']));
        $body = self::b64encode((string) json_encode($payload));
        $signature = self::b64encode($this->sign($header . '.' . $body));

        return $header . '.' . $body . '.' . $signature;
    }

    /**
     * Verifies signature, algorithm, and expiry. Returns the decoded claims.
     *
     * @return array<string, mixed>
     *
     * @throws JwtException on any failure (fail closed — api-security §0.3)
     */
    public function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new JwtException('Malformed token.');
        }
        [$header, $body, $signature] = $parts;

        $decodedHeader = json_decode(self::b64decode($header), true);
        if (!is_array($decodedHeader) || ($decodedHeader['alg'] ?? null) !== self::ALG) {
            throw new JwtException('Unsupported algorithm.');
        }

        $expected = self::b64encode($this->sign($header . '.' . $body));
        if (!hash_equals($expected, $signature)) {
            throw new JwtException('Bad signature.');
        }

        $claims = json_decode(self::b64decode($body), true);
        if (!is_array($claims)) {
            throw new JwtException('Bad payload.');
        }
        if (!isset($claims['exp']) || !is_numeric($claims['exp']) || (int) $claims['exp'] < time()) {
            throw new JwtException('Token expired.');
        }

        /** @var array<string, mixed> $claims */
        return $claims;
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret, true);
    }

    private static function b64encode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function b64decode(string $encoded): string
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}
