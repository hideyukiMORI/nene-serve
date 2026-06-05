<?php

declare(strict_types=1);

namespace NeneServe\Tenant;

/**
 * A single-use, expiring onboarding token. Only the SHA-256 hash of the raw
 * token is persisted; the raw token lives only in the emailed link.
 */
final class Invitation
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $tokenHash,
        public readonly string $status,
        public readonly string $expiresAt,
        public readonly ?string $acceptedAt = null,
    ) {
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(string $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function isAcceptable(string $now): bool
    {
        return $this->isPending() && !$this->isExpired($now);
    }

    public function accepted(string $at): self
    {
        return new self($this->id, $this->organizationId, $this->userId, $this->tokenHash, 'accepted', $this->expiresAt, $at);
    }
}
