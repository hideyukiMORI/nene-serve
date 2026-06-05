<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneServe\Serving\Placement;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/placements (operationId `listPlacements`). Requires `manage_placements`; tenant-scoped. */
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
                items: array_map(static fn (Placement $placement): array => $placement->toAdminArray(), $output->items),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}
