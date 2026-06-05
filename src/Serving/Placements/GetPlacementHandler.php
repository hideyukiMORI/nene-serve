<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/placements/{id} (operationId `getPlacementById`). Requires `manage_placements`; tenant-scoped. */
final readonly class GetPlacementHandler
{
    public function __construct(
        private GetPlacementByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Router::param($request, 'id') ?? '';

        $output = $this->useCase->execute(new GetPlacementByIdInput($id));

        return $this->response->create($output->placement->toAdminArray());
    }
}
