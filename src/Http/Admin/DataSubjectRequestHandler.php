<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Measurement\UseCase\DataSubjectRequestUseCase;
use NeneServe\Tenant\AuthContext;

/**
 * POST /admin/data-subject-requests (operationId `createDataSubjectRequest`).
 * Requires `manage_settings`. `{kind: export|erasure, visitor_bucket}`; tenant-
 * scoped. Erasure is an additive tombstone — counts are unaffected (privacy §5).
 */
final class DataSubjectRequestHandler
{
    public function __construct(
        private readonly DataSubjectRequestUseCase $dsr,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $kind = $body['kind'] ?? null;
        $bucket = $body['visitor_bucket'] ?? null;
        if (!is_string($bucket) || $bucket === '' || !in_array($kind, ['export', 'erasure'], true)) {
            return $this->json->problem(422, 'validation-failed', 'kind (export|erasure) and visitor_bucket are required');
        }

        if ($kind === 'export') {
            return $this->json->ok([
                'kind' => 'export',
                'visitor_bucket' => $bucket,
                'records' => $this->dsr->export($context, $bucket),
            ]);
        }

        return $this->json->ok([
            'kind' => 'erasure',
            'visitor_bucket' => $bucket,
            'tombstoned' => $this->dsr->erase($context, $bucket),
        ]);
    }
}
