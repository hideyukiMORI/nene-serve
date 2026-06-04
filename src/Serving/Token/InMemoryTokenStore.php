<?php

declare(strict_types=1);

namespace NeneServe\Serving\Token;

/**
 * In-memory token store for boot/tests. Production swaps a persistent store
 * (the same contract); event persistence proper lands in #14.
 *
 * `$now` is injectable so token-expiry behaviour is deterministically testable.
 */
final class InMemoryTokenStore implements TokenStoreInterface
{
    /** @var array<string, array{org: string, placement: string, creative: string, recorded: bool}> */
    private array $impressions = [];

    /** @var array<string, array{org: string, placement: string, creative: string, dest: string, expires: int, used: bool}> */
    private array $clicks = [];

    /** @var callable(): int */
    private $now;

    /** @param (callable(): int)|null $now */
    public function __construct(?callable $now = null)
    {
        $this->now = $now ?? static fn (): int => time();
    }

    public function issueImpressionToken(string $organizationId, string $placementId, string $creativeId): string
    {
        $token = self::random();
        $this->impressions[$token] = [
            'org' => $organizationId,
            'placement' => $placementId,
            'creative' => $creativeId,
            'recorded' => false,
        ];

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
        $this->clicks[$token] = [
            'org' => $organizationId,
            'placement' => $placementId,
            'creative' => $creativeId,
            'dest' => $destinationUrl,
            'expires' => ($this->now)() + $ttlSeconds,
            'used' => false,
        ];

        return $token;
    }

    public function recordImpression(string $token): ?ImpressionRecord
    {
        if (!isset($this->impressions[$token])) {
            return null;
        }

        $entry = &$this->impressions[$token];
        $already = $entry['recorded'];
        $entry['recorded'] = true;

        return new ImpressionRecord($entry['org'], $entry['placement'], $entry['creative'], $already);
    }

    public function consumeClickToken(string $token): ?ClickRedirect
    {
        if (!isset($this->clicks[$token])) {
            return null;
        }

        $entry = &$this->clicks[$token];
        if ($entry['used'] || $entry['expires'] < ($this->now)()) {
            return null;
        }

        $entry['used'] = true; // single use

        return new ClickRedirect($entry['org'], $entry['placement'], $entry['creative'], $entry['dest']);
    }

    private static function random(): string
    {
        return bin2hex(random_bytes(16));
    }
}
