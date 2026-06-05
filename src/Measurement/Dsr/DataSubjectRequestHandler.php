<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
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
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        $body = JsonRequestBodyParser::parse($request);
        $kind = $body['kind'] ?? null;
        $bucket = isset($body['visitor_bucket']) && is_string($body['visitor_bucket']) ? $body['visitor_bucket'] : '';

        $errors = [];

        if (!in_array($kind, ['export', 'erasure'], true)) {
            $errors[] = new ValidationError('kind', 'Kind must be export or erasure.', 'invalid');
        }

        if ($bucket === '') {
            $errors[] = new ValidationError('visitor_bucket', 'Visitor bucket is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

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
