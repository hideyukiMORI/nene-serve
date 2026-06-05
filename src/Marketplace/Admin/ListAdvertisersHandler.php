<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use NeneServe\Marketplace\Advertiser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** GET /admin/advertisers (operationId `listAdvertisers`). Requires `manage_marketplace`; tenant-scoped. */
final readonly class ListAdvertisersHandler
{
    public function __construct(
        private ListAdvertisersUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListAdvertisersInput($pagination->limit, $pagination->offset));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(static fn (Advertiser $advertiser): array => $advertiser->toArray(), $output->items),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}
