<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/creatives/{id} (operationId `getCreativeById`). Requires `manage_creatives`; tenant-scoped. */
final readonly class GetCreativeHandler
{
    public function __construct(
        private GetCreativeByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        $output = $this->useCase->execute(new GetCreativeByIdInput($id));

        return $this->response->create($output->creative->toAdminArray());
    }
}
