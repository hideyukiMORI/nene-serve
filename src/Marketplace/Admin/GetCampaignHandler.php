<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/campaigns/{id} (operationId `getCampaign`). Requires
 * `manage_marketplace`. Returns the campaign plus its derived, reproducible
 * spend; aggregate money, no visitor identifiers.
 */
final readonly class GetCampaignHandler
{
    public function __construct(
        private GetCampaignUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        $output = $this->useCase->execute(new GetCampaignInput($id));

        return $this->response->create($output->campaign->toArray() + ['spend' => $output->spend->toArray()]);
    }
}
