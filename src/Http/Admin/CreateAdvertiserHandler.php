<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\UseCase\CreateAdvertiserUseCase;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

/** POST /admin/advertisers (operationId `createAdvertiser`). Requires `manage_marketplace`. */
final class CreateAdvertiserHandler
{
    public function __construct(
        private readonly CreateAdvertiserUseCase $createAdvertiser,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $name = $body['name'] ?? null;
        if (!is_string($name)) {
            return $this->json->problem(422, 'validation-failed', 'name is required');
        }
        $invoiceClientId = is_string($body['invoice_client_id'] ?? null) ? $body['invoice_client_id'] : null;

        try {
            $advertiser = $this->createAdvertiser->execute($context, $name, $invoiceClientId);
        } catch (MarketplaceValidationException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid advertiser', $e->getMessage());
        }

        return $this->json->ok($advertiser->toArray(), 201);
    }
}
