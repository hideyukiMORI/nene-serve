<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Marketplace\BillingPeriodRepositoryInterface;
use NeneServe\Marketplace\SpendSnapshotRepositoryInterface;
use NeneServe\Marketplace\UseCase\BillingPeriodNotFoundException;

final readonly class GetBillingPeriodUseCase implements GetBillingPeriodUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private BillingPeriodRepositoryInterface $periods,
        private SpendSnapshotRepositoryInterface $snapshots,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(GetBillingPeriodInput $input): GetBillingPeriodOutput
    {
        $organizationId = $this->organizationId->get();

        $period = $this->periods->findByIdInOrganization($input->id, $organizationId);

        if ($period === null) {
            throw new BillingPeriodNotFoundException();
        }

        return new GetBillingPeriodOutput($period, $this->snapshots->latestForPeriod($organizationId, $period->id));
    }
}
