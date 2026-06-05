<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\RequestScopedHolder;
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
                static function (ContainerInterface $c): OpenBillingPeriodUseCaseInterface {
                    $campaigns = $c->get(CampaignRepositoryInterface::class);

                    if (!$campaigns instanceof CampaignRepositoryInterface) {
                        throw new LogicException('Campaign repository service is invalid.');
                    }

                    return new OpenBillingPeriodUseCase($campaigns, self::transactions($c), self::orgId($c));
                },
            )
            ->set(
                CloseBillingPeriodUseCaseInterface::class,
                static function (ContainerInterface $c): CloseBillingPeriodUseCaseInterface {
                    $spend = $c->get(GetCampaignSpendUseCase::class);

                    if (!$spend instanceof GetCampaignSpendUseCase) {
                        throw new LogicException('Get campaign spend use case service is invalid.');
                    }

                    return new CloseBillingPeriodUseCase(self::query($c), self::transactions($c), $spend, self::orgId($c));
                },
            )
            ->set(
                HandoffBillingPeriodUseCaseInterface::class,
                static function (ContainerInterface $c): HandoffBillingPeriodUseCaseInterface {
                    $invoice = $c->get(InvoiceClientInterface::class);

                    if (!$invoice instanceof InvoiceClientInterface) {
                        throw new LogicException('Invoice client service is invalid.');
                    }

                    return new HandoffBillingPeriodUseCase(self::query($c), self::transactions($c), $invoice, self::orgId($c));
                },
            )
            ->set(
                OpenBillingPeriodHandler::class,
                static function (ContainerInterface $c): OpenBillingPeriodHandler {
                    $useCase = $c->get(OpenBillingPeriodUseCaseInterface::class);

                    if (!$useCase instanceof OpenBillingPeriodUseCaseInterface) {
                        throw new LogicException('Open billing period use case service is invalid.');
                    }

                    return new OpenBillingPeriodHandler($useCase, self::json($c));
                },
            )
            ->set(
                CloseBillingPeriodHandler::class,
                static function (ContainerInterface $c): CloseBillingPeriodHandler {
                    $useCase = $c->get(CloseBillingPeriodUseCaseInterface::class);

                    if (!$useCase instanceof CloseBillingPeriodUseCaseInterface) {
                        throw new LogicException('Close billing period use case service is invalid.');
                    }

                    return new CloseBillingPeriodHandler($useCase, self::json($c));
                },
            )
            ->set(
                GetBillingPeriodUseCaseInterface::class,
                static function (ContainerInterface $c): GetBillingPeriodUseCaseInterface {
                    $periods = $c->get(BillingPeriodRepositoryInterface::class);
                    $snapshots = $c->get(SpendSnapshotRepositoryInterface::class);

                    if (!$periods instanceof BillingPeriodRepositoryInterface) {
                        throw new LogicException('Billing period repository service is invalid.');
                    }

                    if (!$snapshots instanceof SpendSnapshotRepositoryInterface) {
                        throw new LogicException('Spend snapshot repository service is invalid.');
                    }

                    return new GetBillingPeriodUseCase($periods, $snapshots, self::orgId($c));
                },
            )
            ->set(
                GetBillingPeriodHandler::class,
                static function (ContainerInterface $c): GetBillingPeriodHandler {
                    $useCase = $c->get(GetBillingPeriodUseCaseInterface::class);

                    if (!$useCase instanceof GetBillingPeriodUseCaseInterface) {
                        throw new LogicException('Get billing period use case service is invalid.');
                    }

                    return new GetBillingPeriodHandler($useCase, self::json($c));
                },
            )
            ->set(
                HandoffBillingPeriodHandler::class,
                static function (ContainerInterface $c): HandoffBillingPeriodHandler {
                    $useCase = $c->get(HandoffBillingPeriodUseCaseInterface::class);

                    if (!$useCase instanceof HandoffBillingPeriodUseCaseInterface) {
                        throw new LogicException('Handoff billing period use case service is invalid.');
                    }

                    return new HandoffBillingPeriodHandler($useCase, self::json($c));
                },
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
                static function (ContainerInterface $container): BillingRouteRegistrar {
                    $open = $container->get(OpenBillingPeriodHandler::class);
                    $close = $container->get(CloseBillingPeriodHandler::class);
                    $get = $container->get(GetBillingPeriodHandler::class);
                    $handoff = $container->get(HandoffBillingPeriodHandler::class);

                    if (!$open instanceof OpenBillingPeriodHandler) {
                        throw new LogicException('Open billing period handler service is invalid.');
                    }

                    if (!$close instanceof CloseBillingPeriodHandler) {
                        throw new LogicException('Close billing period handler service is invalid.');
                    }

                    if (!$get instanceof GetBillingPeriodHandler) {
                        throw new LogicException('Get billing period handler service is invalid.');
                    }

                    if (!$handoff instanceof HandoffBillingPeriodHandler) {
                        throw new LogicException('Handoff billing period handler service is invalid.');
                    }

                    return new BillingRouteRegistrar($open, $close, $get, $handoff);
                },
            );
    }

    /** @return RequestScopedHolder<string> */
}
