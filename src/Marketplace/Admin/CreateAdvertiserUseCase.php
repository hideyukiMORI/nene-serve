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
use NeneServe\Support\Id;
use NeneServe\Support\SqlDialect;

final readonly class CreateAdvertiserUseCase implements CreateAdvertiserUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function execute(CreateAdvertiserInput $input): CreateAdvertiserOutput
    {
        if (trim($input->name) === '') {
            throw new MarketplaceValidationException('name is required.');
        }

        $advertiser = new Advertiser(
            Id::generate('adv'),
            $this->organizationId->get(),
            trim($input->name),
            'active',
            $input->invoiceClientId,
        );

        $dialect = $this->dialect;
        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($advertiser, $input, $dialect): Advertiser {
                (new PdoAdvertiserRepository($tx, $dialect))->save($advertiser);
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
