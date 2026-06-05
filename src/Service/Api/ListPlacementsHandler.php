<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneServe\Serving\Placement;
use NeneServe\Serving\Placements\ListPlacementsInput;
use NeneServe\Serving\Placements\ListPlacementsUseCaseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/placements (operationId `listPlacements`, service surface). Requires
 * the `read:placements` scope (enforced by {@see \NeneServe\Service\Auth\ScopeMiddleware})
 * and returns only the token's tenant (ADR 0006) — read from the org holder set
 * by {@see \NeneServe\Service\Auth\ServiceAuthMiddleware}. Reuses the admin list
 * use-case; exposes the public projection only.
 */
final readonly class ListPlacementsHandler
{
    public function __construct(
        private ListPlacementsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListPlacementsInput($pagination->limit, $pagination->offset));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (Placement $p): array => [
                        'id' => $p->id,
                        'public_placement_key' => $p->publicPlacementKey,
                        'status' => $p->status,
                    ],
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}
