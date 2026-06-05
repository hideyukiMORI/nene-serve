<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Serving\PlacementRepositoryInterface;

final readonly class ListPlacementsUseCase implements ListPlacementsUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private PlacementRepositoryInterface $placements,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(ListPlacementsInput $input): ListPlacementsOutput
    {
        return new ListPlacementsOutput(
            items: $this->placements->listByOrganization($this->organizationId->get(), $input->limit, $input->offset),
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
