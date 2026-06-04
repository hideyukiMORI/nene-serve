<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Marketplace\Advertiser;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

final class CreateAdvertiserUseCase
{
    public function __construct(
        private readonly AdvertiserRepositoryInterface $advertisers,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
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

        return $this->tx->transactional(function () use ($advertiser, $actor): Advertiser {
            $this->advertisers->save($advertiser);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'advertiser.created',
                'advertiser',
                $advertiser->id,
                ['after' => ['name' => $advertiser->name]],
            );

            return $advertiser;
        });
    }
}
