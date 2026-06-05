<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\UseCase\CreativeNotFoundException;

final readonly class GetCreativeByIdUseCase implements GetCreativeByIdUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private CreativeRepositoryInterface $creatives,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(GetCreativeByIdInput $input): GetCreativeByIdOutput
    {
        $creative = $this->creatives->findByIdInOrganization($input->id, $this->organizationId->get());

        if ($creative === null) {
            throw new CreativeNotFoundException();
        }

        return new GetCreativeByIdOutput($creative);
    }
}
