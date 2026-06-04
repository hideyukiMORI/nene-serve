<?php

declare(strict_types=1);

namespace NeneServe\Mcp;

/**
 * A proposed delivery-plan change from an MCP / automation caller (api-security
 * §5, ADR 0018): writes are **plans** that require an explicit confirmation step
 * before they take effect, and every proposal/application is audited. The plan id
 * doubles as the opaque **confirmation token** needed to apply it.
 *
 * MVP target: change a placement's default creative (a delivery-plan change).
 */
final class ChangePlan
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly string $placementId,
        public readonly string $newCreativeId,
        public readonly string $status = 'proposed',
        public readonly string $createdAt = '',
    ) {
    }

    public function isProposed(): bool
    {
        return $this->status === 'proposed';
    }

    public function withStatus(string $status): self
    {
        return new self($this->id, $this->organizationId, $this->placementId, $this->newCreativeId, $status, $this->createdAt);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'plan_id' => $this->id,
            'confirmation_token' => $this->id,
            'type' => 'placement.default_creative',
            'placement_id' => $this->placementId,
            'new_creative_id' => $this->newCreativeId,
            'status' => $this->status,
        ];
    }
}
