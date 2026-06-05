<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use NeneServe\Marketplace\Advertiser;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

interface CreateAdvertiserUseCaseInterface
{
    /** @throws MarketplaceValidationException */
    public function execute(AuthContext $actor, string $name, ?string $invoiceClientId = null): Advertiser;
}
