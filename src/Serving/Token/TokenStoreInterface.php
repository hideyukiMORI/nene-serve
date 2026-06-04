<?php

declare(strict_types=1);

namespace NeneServe\Serving\Token;

/**
 * Issues and validates the opaque public-surface tokens (ADR 0019).
 *
 * - `impression_token`: idempotent — replay does not inflate counts (§4).
 * - `click_token`: single-use with a short TTL; expired/used/unknown all return
 *   null so the redirect endpoint can fail closed (§2).
 *
 * Tokens are opaque random strings; they never encode internal numeric ids.
 */
interface TokenStoreInterface
{
    public function issueImpressionToken(string $organizationId, string $placementId, string $creativeId): string;

    public function issueClickToken(
        string $organizationId,
        string $placementId,
        string $creativeId,
        string $destinationUrl,
        int $ttlSeconds,
    ): string;

    public function recordImpression(string $token): ?ImpressionRecord;

    public function consumeClickToken(string $token): ?ClickRedirect;
}
