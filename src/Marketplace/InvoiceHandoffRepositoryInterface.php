<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

interface InvoiceHandoffRepositoryInterface
{
    public function findByExternalReference(string $organizationId, string $externalReference): ?InvoiceHandoff;

    public function save(InvoiceHandoff $handoff): void;
}
