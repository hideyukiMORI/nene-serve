<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Http\BodyFieldCollector;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/data-subject-requests (operationId `createDataSubjectRequest`).
 * Requires `manage_settings`. `{kind: export|erasure, visitor_bucket}`; tenant-
 * scoped. Erasure is an additive tombstone — counts are unaffected (privacy §5).
 */
final readonly class DataSubjectRequestHandler
{
    public function __construct(
        private DataSubjectRequestUseCaseInterface $dsr,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $body = JsonRequestBodyParser::parse($request);
        $fields = new BodyFieldCollector($body);
        $kind = $fields->oneOf('kind', ['export', 'erasure'], 'Kind must be export or erasure.');
        $bucket = $fields->requiredString('visitor_bucket', 'Visitor bucket is required.');
        $fields->throwIfInvalid();

        if ($kind === 'export') {
            return $this->response->create([
                'kind' => 'export',
                'visitor_bucket' => $bucket,
                'records' => $this->dsr->export(new ExportVisitorDataInput($context->userId, $bucket))->records,
            ]);
        }

        return $this->response->create([
            'kind' => 'erasure',
            'visitor_bucket' => $bucket,
            'tombstoned' => $this->dsr->erase(new EraseVisitorDataInput($context->userId, $bucket))->tombstoned,
        ]);
    }
}
