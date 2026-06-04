<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Marketplace\UseCase\CampaignNotFoundException;
use NeneServe\Marketplace\UseCase\DealHandoffFailedException;
use NeneServe\Marketplace\UseCase\HandoffCampaignToDealUseCase;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/campaigns/{id}/deal-handoff (operationId `handoffCampaignToDeal`).
 * Requires `manage_marketplace`. Idempotent; a transport failure (502) does not
 * affect serving and is retryable.
 */
final class HandoffCampaignToDealHandler
{
    public function __construct(
        private readonly HandoffCampaignToDealUseCase $handoff,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        try {
            $opportunity = $this->handoff->execute($context, (string) $request->param('id'));
        } catch (CampaignNotFoundException) {
            return $this->json->problem(404, 'campaign-not-found', 'Campaign not found');
        } catch (DealHandoffFailedException $e) {
            return $this->json->problem(502, 'deal-handoff-failed', 'Deal handoff failed', $e->getMessage());
        }

        return $this->json->ok($opportunity->toArray());
    }
}
