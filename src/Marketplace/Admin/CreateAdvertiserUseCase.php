<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\Advertiser;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\UseCase\MarketplaceValidationException;
use NeneServe\Tenant\AuthContext;

final readonly class CreateAdvertiserUseCase implements CreateAdvertiserUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
    ) {
    }

    public function execute(AuthContext $actor, string $name, ?string $invoiceClientId = null): Advertiser
    {
        if (trim($name) === '') {
            throw new MarketplaceValidationException('name is required.');
        }

        $advertiser = new Advertiser(
            'adv-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            trim($name),
            'active',
            $invoiceClientId,
        );

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($advertiser, $actor): Advertiser {
                (new PdoAdvertiserRepository($tx))->save($advertiser);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'advertiser.created',
                    'advertiser',
                    $advertiser->id,
                    ['after' => ['name' => $advertiser->name]],
                );

                return $advertiser;
            },
        );
    }
}
