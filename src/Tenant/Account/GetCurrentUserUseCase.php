<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Account;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Tenant\UserRepositoryInterface;

final readonly class GetCurrentUserUseCase implements GetCurrentUserUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private UserRepositoryInterface $users,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(GetCurrentUserInput $input): GetCurrentUserOutput
    {
        return new GetCurrentUserOutput(
            $this->users->findByIdInOrganization($input->userId, $this->organizationId->get()),
        );
    }
}
