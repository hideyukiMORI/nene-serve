<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Retention\UseCase\LegalHoldException;
use NeneServe\Retention\UseCase\PlaceLegalHoldUseCase;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/legal-holds (operationId `placeLegalHold`). Requires
 * `manage_settings`. While any hold is active, retention purges are blocked
 * (billing §7, ADR 0022 §7). Audited.
 */
final class PlaceLegalHoldHandler
{
    public function __construct(
        private readonly PlaceLegalHoldUseCase $legalHolds,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $reason = $request->json()['reason'] ?? null;
        if (!is_string($reason)) {
            return $this->json->problem(422, 'validation-failed', 'reason is required');
        }

        try {
            $hold = $this->legalHolds->place($context, $reason);
        } catch (LegalHoldException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid legal hold', $e->getMessage());
        }

        return $this->json->ok($hold->toArray(), 201);
    }
}
