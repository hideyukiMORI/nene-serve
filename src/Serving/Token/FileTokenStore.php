<?php

declare(strict_types=1);

namespace NeneServe\Serving\Token;

use Nene2\Http\SecureTokenHelper;
use NeneServe\Support\LockedJsonFile;

/**
 * File-backed token store so the serve → click flow survives across separate
 * HTTP requests on a single-server dev boot (the in-memory store is per-request).
 * Single JSON file guarded by an exclusive lock; adequate for one server, not a
 * cluster. Production swaps a shared store (Redis/DB) behind the same contract;
 * durable event persistence lands in #14.
 */
final class FileTokenStore implements TokenStoreInterface
{
    use LockedJsonFile;

    public function __construct(
        private readonly string $path,
    ) {
    }

    public function issueImpressionToken(string $organizationId, string $placementId, string $creativeId): string
    {
        $token = self::random();
        $this->mutate(function (array &$state) use ($token, $organizationId, $placementId, $creativeId): void {
            $state['impressions'][$token] = [
                'org' => $organizationId,
                'placement' => $placementId,
                'creative' => $creativeId,
                'recorded' => false,
            ];
        });

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
        $this->mutate(function (array &$state) use ($token, $organizationId, $placementId, $creativeId, $destinationUrl, $ttlSeconds): void {
            $state['clicks'][$token] = [
                'org' => $organizationId,
                'placement' => $placementId,
                'creative' => $creativeId,
                'dest' => $destinationUrl,
                'expires' => time() + $ttlSeconds,
                'used' => false,
            ];
        });

        return $token;
    }

    public function recordImpression(string $token): ?ImpressionRecord
    {
        $result = null;
        $this->mutate(function (array &$state) use ($token, &$result): void {
            if (!isset($state['impressions'][$token])) {
                return;
            }
            $entry = &$state['impressions'][$token];
            $already = (bool) $entry['recorded'];
            $entry['recorded'] = true;
            $result = new ImpressionRecord(
                (string) $entry['org'],
                (string) $entry['placement'],
                (string) $entry['creative'],
                $already,
            );
        });

        return $result;
    }

    public function consumeClickToken(string $token): ?ClickRedirect
    {
        $result = null;
        $this->mutate(function (array &$state) use ($token, &$result): void {
            if (!isset($state['clicks'][$token])) {
                return;
            }
            $entry = &$state['clicks'][$token];
            if ((bool) $entry['used'] || (int) $entry['expires'] < time()) {
                return;
            }
            $entry['used'] = true;
            $result = new ClickRedirect(
                (string) $entry['org'],
                (string) $entry['placement'],
                (string) $entry['creative'],
                (string) $entry['dest'],
            );
        });

        return $result;
    }

    public function issueFrameToken(string $organizationId, string $creativeId, int $ttlSeconds): string
    {
        $token = self::random();
        $this->mutate(function (array &$state) use ($token, $organizationId, $creativeId, $ttlSeconds): void {
            $state['frames'][$token] = [
                'org' => $organizationId,
                'creative' => $creativeId,
                'expires' => time() + $ttlSeconds,
            ];
        });

        return $token;
    }

    public function resolveFrameToken(string $token): ?FrameTarget
    {
        $result = null;
        $this->mutate(function (array &$state) use ($token, &$result): void {
            if (!isset($state['frames'][$token])) {
                return;
            }
            $entry = $state['frames'][$token];
            if ((int) $entry['expires'] < time()) {
                return;
            }
            $result = new FrameTarget((string) $entry['org'], (string) $entry['creative']);
        });

        return $result;
    }

    /**
     * Loads state, applies $apply, and writes it back under an exclusive lock.
     *
     * @param callable(array{impressions: array<string, mixed>, clicks: array<string, mixed>, frames: array<string, mixed>}): void $apply
     */
    private function mutate(callable $apply): void
    {
        $this->withLockedFile($this->path, static function (string $raw) use ($apply): string {
            /** @var array{impressions?: array<string, mixed>, clicks?: array<string, mixed>, frames?: array<string, mixed>} $state */
            $state = $raw === '' ? [] : (json_decode($raw, true) ?: []);
            $state += ['impressions' => [], 'clicks' => [], 'frames' => []];

            $apply($state);

            return (string) json_encode($state);
        });
    }

    private static function random(): string
    {
        return SecureTokenHelper::generate(16);
    }
}
