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
 * POST /admin/legal-holds/{id}/release (operationId `releaseLegalHold`). Requires
 * `manage_settings`. Audited; the hold is tombstoned (released_at), never deleted.
 */
final class ReleaseLegalHoldHandler
{
    public function __construct(
        private readonly PlaceLegalHoldUseCase $legalHolds,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        try {
            $hold = $this->legalHolds->release($context, (string) $request->param('id'));
        } catch (LegalHoldException $e) {
            return $this->json->problem(422, 'validation-failed', 'Invalid legal hold', $e->getMessage());
        }

        return $this->json->ok($hold->toArray());
    }
}
