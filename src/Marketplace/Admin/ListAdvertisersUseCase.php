<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;

final readonly class ListAdvertisersUseCase implements ListAdvertisersUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private AdvertiserRepositoryInterface $advertisers,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(ListAdvertisersInput $input): ListAdvertisersOutput
    {
        return new ListAdvertisersOutput(
            items: $this->advertisers->listByOrganization($this->organizationId->get(), $input->limit, $input->offset),
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
