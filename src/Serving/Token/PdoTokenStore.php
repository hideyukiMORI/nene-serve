<?php

declare(strict_types=1);

namespace NeneServe\Serving\Token;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\SecureTokenHelper;
use Nene2\Http\UtcClock;

/**
 * Public-surface tokens in the database, shared by every process and host.
 *
 * The file store this replaces is correct on one host and quietly wrong on two:
 * each host writes its own `var/tokens.json`, so a click token issued by one and
 * redeemed on the other simply does not exist, and the redirect fails closed on
 * a token that was perfectly valid. Nothing logs an error — the visitor just
 * does not arrive (#207).
 *
 * **The raw token is never stored.** Only its SHA-256 hash, the same rule
 * `service_tokens` follows. Reading this table does not hand over usable click
 * tokens.
 *
 * **Consumption is a conditional UPDATE, not read-then-write.** A click token is
 * single-use (ADR 0019 §2), and the file store enforced that with a file lock —
 * which is exactly the guarantee that stops holding once a second host exists.
 * Here the redemption is `UPDATE … WHERE consumed_at IS NULL`, so of two
 * concurrent redemptions the database picks one winner and the loser sees zero
 * affected rows. The same shape makes impression tokens idempotent: the flip
 * either happens or reports that it already had.
 */
final readonly class PdoTokenStore implements TokenStoreInterface
{
    private const TABLE = 'public_tokens';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private ClockInterface $clock = new UtcClock(),
    ) {
    }

    public function issueImpressionToken(string $organizationId, string $placementId, string $creativeId): string
    {
        $token = self::random();

        $this->query->execute(
            'INSERT INTO ' . self::TABLE . ' (token_hash, kind, organization_id, placement_id, creative_id)
             VALUES (?, ?, ?, ?, ?)',
            [SecureTokenHelper::hash($token), 'impression', $organizationId, $placementId, $creativeId],
        );

        return $token;
    }

    public function issueClickToken(
        string $organizationId,
        string $placementId,
        string $creativeId,
        string $destinationUrl,
        int $ttlSeconds,
    ): string {
        $token = self::random();

        $this->query->execute(
            'INSERT INTO ' . self::TABLE . '
                (token_hash, kind, organization_id, placement_id, creative_id, destination_url, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                SecureTokenHelper::hash($token),
                'click',
                $organizationId,
                $placementId,
                $creativeId,
                $destinationUrl,
                $this->now() + $ttlSeconds,
            ],
        );

        return $token;
    }

    public function recordImpression(string $token): ?ImpressionRecord
    {
        $hash = SecureTokenHelper::hash($token);

        // Flip first, then read: whoever flips it is the first recording, and a
        // replay flips nothing. Reading first would let two beacons both see
        // "not recorded" and double count (ADR 0015).
        $flipped = $this->query->execute(
            'UPDATE ' . self::TABLE . " SET recorded_at = ?
             WHERE token_hash = ? AND kind = 'impression' AND recorded_at IS NULL",
            [$this->now(), $hash],
        );

        $row = $this->query->fetchOne(
            'SELECT organization_id, placement_id, creative_id FROM ' . self::TABLE . "
             WHERE token_hash = ? AND kind = 'impression'",
            [$hash],
        );

        if ($row === null) {
            return null; // unknown token
        }

        return new ImpressionRecord(
            (string) $row['organization_id'],
            (string) $row['placement_id'],
            (string) $row['creative_id'],
            $flipped === 0, // nothing to flip means it had already been recorded
        );
    }

    public function consumeClickToken(string $token): ?ClickRedirect
    {
        $hash = SecureTokenHelper::hash($token);

        // Single-use and unexpired are both conditions of the write, so the
        // check cannot drift from the consumption.
        $consumed = $this->query->execute(
            'UPDATE ' . self::TABLE . " SET consumed_at = ?
             WHERE token_hash = ? AND kind = 'click' AND consumed_at IS NULL AND expires_at >= ?",
            [$this->now(), $hash, $this->now()],
        );

        if ($consumed === 0) {
            return null; // unknown, already used, or expired — all fail closed
        }

        $row = $this->query->fetchOne(
            'SELECT organization_id, placement_id, creative_id, destination_url FROM ' . self::TABLE . '
             WHERE token_hash = ?',
            [$hash],
        );

        if ($row === null) {
            return null;
        }

        return new ClickRedirect(
            (string) $row['organization_id'],
            (string) $row['placement_id'],
            (string) $row['creative_id'],
            (string) $row['destination_url'],
        );
    }

    public function issueFrameToken(string $organizationId, string $creativeId, int $ttlSeconds): string
    {
        $token = self::random();

        $this->query->execute(
            'INSERT INTO ' . self::TABLE . ' (token_hash, kind, organization_id, creative_id, expires_at)
             VALUES (?, ?, ?, ?, ?)',
            [SecureTokenHelper::hash($token), 'frame', $organizationId, $creativeId, $this->now() + $ttlSeconds],
        );

        return $token;
    }

    public function resolveFrameToken(string $token): ?FrameTarget
    {
        $row = $this->query->fetchOne(
            'SELECT organization_id, creative_id FROM ' . self::TABLE . "
             WHERE token_hash = ? AND kind = 'frame' AND expires_at >= ?",
            [SecureTokenHelper::hash($token), $this->now()],
        );

        if ($row === null) {
            return null;
        }

        return new FrameTarget((string) $row['organization_id'], (string) $row['creative_id']);
    }

    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }

    private static function random(): string
    {
        return SecureTokenHelper::generate(16);
    }
}
