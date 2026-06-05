<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Serving\UseCase\PlacementNotFoundException;

final readonly class GetPlacementByIdUseCase implements GetPlacementByIdUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private PlacementRepositoryInterface $placements,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(GetPlacementByIdInput $input): GetPlacementByIdOutput
    {
        $placement = $this->placements->findByIdInOrganization($input->id, $this->organizationId->get());

        if ($placement === null) {
            throw new PlacementNotFoundException();
        }

        return new GetPlacementByIdOutput($placement);
    }
}
