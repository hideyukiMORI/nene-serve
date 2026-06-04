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

    /**
     * Opaque, reusable (within TTL) token for the HTML5 sandbox frame URL, so
     * the public payload never exposes the internal creative id (api-security §2).
     */
    public function issueFrameToken(string $organizationId, string $creativeId, int $ttlSeconds): string;

    public function resolveFrameToken(string $token): ?FrameTarget;
}
