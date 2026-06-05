<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\Advertiser;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;

final readonly class CreateAdvertiserUseCase implements CreateAdvertiserUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(CreateAdvertiserInput $input): CreateAdvertiserOutput
    {
        if (trim($input->name) === '') {
            throw new MarketplaceValidationException('name is required.');
        }

        $advertiser = new Advertiser(
            'adv-' . bin2hex(random_bytes(8)),
            $this->organizationId->get(),
            trim($input->name),
            'active',
            $input->invoiceClientId,
        );

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($advertiser, $input): Advertiser {
                (new PdoAdvertiserRepository($tx))->save($advertiser);
                (new PdoAuditLog($tx))->record(
                    $advertiser->organizationId,
                    $input->actorUserId,
                    'advertiser.created',
                    'advertiser',
                    $advertiser->id,
                    ['after' => ['name' => $advertiser->name]],
                );

                return $advertiser;
            },
        );

        return new CreateAdvertiserOutput($stored);
    }
}
