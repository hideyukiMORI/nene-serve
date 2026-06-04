<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

final class InMemoryInvoiceHandoffRepository implements InvoiceHandoffRepositoryInterface
{
    /** @var array<string, InvoiceHandoff> keyed by externalReference */
    private array $handoffs = [];

    public function findByExternalReference(string $organizationId, string $externalReference): ?InvoiceHandoff
    {
        $handoff = $this->handoffs[$externalReference] ?? null;

        return ($handoff !== null && $handoff->organizationId === $organizationId) ? $handoff : null;
    }

    public function save(InvoiceHandoff $handoff): void
    {
        $this->handoffs[$handoff->externalReference] = $handoff;
    }
}
