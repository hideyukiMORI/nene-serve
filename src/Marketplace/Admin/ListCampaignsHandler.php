<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneServe\Marketplace\Campaign;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/campaigns (operationId `listCampaigns`). Requires `manage_marketplace`; tenant-scoped. */
final readonly class ListCampaignsHandler
{
    public function __construct(
        private ListCampaignsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListCampaignsInput($pagination->limit, $pagination->offset));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(static fn (Campaign $campaign): array => $campaign->toArray(), $output->items),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}
