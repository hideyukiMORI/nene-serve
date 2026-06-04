<?php

declare(strict_types=1);

namespace NeneServe\Retention;

/**
 * A legal hold suspends retention purges for a tenant (billing §7, ADR 0022 §7):
 * while any hold is active, no data is physically removed. Released holds are
 * tombstoned (released_at), never deleted.
 */
final class LegalHold
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $reason,
        public readonly string $placedAt,
        public readonly ?string $releasedAt = null,
    ) {
    }

    public function isActive(): bool
    {
        return $this->releasedAt === null;
    }

    public function release(string $at): self
    {
        return new self($this->id, $this->organizationId, $this->reason, $this->placedAt, $at);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'placed_at' => $this->placedAt,
            'released_at' => $this->releasedAt,
            'active' => $this->isActive(),
        ];
    }
}
