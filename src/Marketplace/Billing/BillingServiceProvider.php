<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Marketplace\BillingPeriodRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\Invoice\InvoiceClientInterface;
use NeneServe\Marketplace\PdoBillingPeriodRepository;
use NeneServe\Marketplace\PdoSpendSnapshotRepository;
use NeneServe\Marketplace\SpendSnapshotRepositoryInterface;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;

final readonly class BillingServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.billing';

    public const string EXCEPTION_HANDLER_NOT_FOUND = 'nene-serve.exception_handler.billing_period_not_found';

    public const string EXCEPTION_HANDLER_TRANSITION = 'nene-serve.exception_handler.invalid_period_transition';

    public const string EXCEPTION_HANDLER_RECONCILIATION = 'nene-serve.exception_handler.reconciliation_failed';

    public const string EXCEPTION_HANDLER_INVOICE_HANDOFF = 'nene-serve.exception_handler.invoice_handoff_failed';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                BillingPeriodRepositoryInterface::class,
                static fn (ContainerInterface $c): BillingPeriodRepositoryInterface => new PdoBillingPeriodRepository(self::query($c)),
            )
            ->set(
                SpendSnapshotRepositoryInterface::class,
                static fn (ContainerInterface $c): SpendSnapshotRepositoryInterface => new PdoSpendSnapshotRepository(self::query($c)),
            )
            ->set(
                OpenBillingPeriodUseCaseInterface::class,
                static fn (ContainerInterface $c): OpenBillingPeriodUseCaseInterface => new OpenBillingPeriodUseCase(
                    self::service($c, CampaignRepositoryInterface::class),
                    self::transactions($c),
                    self::orgId($c),
                ),
            )
            ->set(
                CloseBillingPeriodUseCaseInterface::class,
                static fn (ContainerInterface $c): CloseBillingPeriodUseCaseInterface => new CloseBillingPeriodUseCase(
                    self::query($c),
                    self::transactions($c),
                    self::service($c, GetCampaignSpendUseCase::class),
                    self::orgId($c),
                ),
            )
            ->set(
                HandoffBillingPeriodUseCaseInterface::class,
                static fn (ContainerInterface $c): HandoffBillingPeriodUseCaseInterface => new HandoffBillingPeriodUseCase(
                    self::query($c),
                    self::transactions($c),
                    self::service($c, InvoiceClientInterface::class),
                    self::orgId($c),
                ),
            )
            ->set(
                OpenBillingPeriodHandler::class,
                static fn (ContainerInterface $c): OpenBillingPeriodHandler => new OpenBillingPeriodHandler(self::service($c, OpenBillingPeriodUseCaseInterface::class), self::json($c)),
            )
            ->set(
                CloseBillingPeriodHandler::class,
                static fn (ContainerInterface $c): CloseBillingPeriodHandler => new CloseBillingPeriodHandler(self::service($c, CloseBillingPeriodUseCaseInterface::class), self::json($c)),
            )
            ->set(
                GetBillingPeriodUseCaseInterface::class,
                static fn (ContainerInterface $c): GetBillingPeriodUseCaseInterface => new GetBillingPeriodUseCase(
                    self::service($c, BillingPeriodRepositoryInterface::class),
                    self::service($c, SpendSnapshotRepositoryInterface::class),
                    self::orgId($c),
                ),
            )
            ->set(
                GetBillingPeriodHandler::class,
                static fn (ContainerInterface $c): GetBillingPeriodHandler => new GetBillingPeriodHandler(self::service($c, GetBillingPeriodUseCaseInterface::class), self::json($c)),
            )
            ->set(
                HandoffBillingPeriodHandler::class,
                static fn (ContainerInterface $c): HandoffBillingPeriodHandler => new HandoffBillingPeriodHandler(self::service($c, HandoffBillingPeriodUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_NOT_FOUND,
                static fn (ContainerInterface $c): BillingPeriodNotFoundExceptionHandler => new BillingPeriodNotFoundExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_TRANSITION,
                static fn (ContainerInterface $c): InvalidPeriodTransitionExceptionHandler => new InvalidPeriodTransitionExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_RECONCILIATION,
                static fn (ContainerInterface $c): ReconciliationFailedExceptionHandler => new ReconciliationFailedExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_INVOICE_HANDOFF,
                static fn (ContainerInterface $c): InvoiceHandoffFailedExceptionHandler => new InvoiceHandoffFailedExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): BillingRouteRegistrar => new BillingRouteRegistrar(
                    self::service($c, OpenBillingPeriodHandler::class),
                    self::service($c, CloseBillingPeriodHandler::class),
                    self::service($c, GetBillingPeriodHandler::class),
                    self::service($c, HandoffBillingPeriodHandler::class),
                ),
            );
    }
}
