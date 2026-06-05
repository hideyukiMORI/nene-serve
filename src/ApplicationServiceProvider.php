<?php

declare(strict_types=1);

namespace NeneServe;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use NeneServe\Assets\Admin\AssetsServiceProvider;
use NeneServe\Auth\AuthServiceProvider;
use NeneServe\Health\HealthServiceProvider;
use NeneServe\Marketplace\Admin\MarketplaceServiceProvider;
use NeneServe\Marketplace\Billing\BillingServiceProvider;
use NeneServe\Marketplace\Deal\DealServiceProvider;
use NeneServe\Measurement\Dsr\DsrServiceProvider;
use NeneServe\Measurement\Metrics\MetricsServiceProvider;
use NeneServe\Retention\LegalHolds\LegalHoldsServiceProvider;
use NeneServe\Service\Api\ServiceApiServiceProvider;
use NeneServe\Serving\Creatives\CreativesServiceProvider;
use NeneServe\Serving\Placements\PlacementsServiceProvider;
use NeneServe\Serving\PublicApi\PublicServiceProvider;
use NeneServe\Settings\SettingsServiceProvider;
use NeneServe\Tenant\Account\AccountServiceProvider;
use NeneServe\Tenant\Invitations\InvitationsServiceProvider;
use NeneServe\Tenant\Users\UsersServiceProvider;
use Psr\Container\ContainerInterface;

/**
 * Aggregates the domain wiring for the NENE2 runtime: every domain registers its
 * services through its own provider and contributes a route registrar and any
 * problem-details exception handlers, collected here for the
 * {@see \Nene2\Http\RuntimeApplicationFactory}.
 *
 * Domain modules are added in Phase 2 of the NENE2 migration; the lists are
 * empty until each domain is ported (see docs/development/nene2-compliance.md).
 */
final readonly class ApplicationServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRARS = 'nene-serve.route_registrars';

    public const string EXCEPTION_HANDLERS = 'nene-serve.exception_handlers';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->addProvider(new HealthServiceProvider())
            ->addProvider(new AuthServiceProvider())
            ->addProvider(new AccountServiceProvider())
            ->addProvider(new SettingsServiceProvider())
            ->addProvider(new UsersServiceProvider())
            ->addProvider(new MetricsServiceProvider())
            ->addProvider(new PlacementsServiceProvider())
            ->addProvider(new CreativesServiceProvider())
            ->addProvider(new MarketplaceServiceProvider())
            ->addProvider(new InvitationsServiceProvider())
            ->addProvider(new LegalHoldsServiceProvider())
            ->addProvider(new DsrServiceProvider())
            ->addProvider(new BillingServiceProvider())
            ->addProvider(new DealServiceProvider())
            ->addProvider(new AssetsServiceProvider())
            ->addProvider(new PublicServiceProvider())
            ->addProvider(new ServiceApiServiceProvider());

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $registrars = [
                        $container->get(HealthServiceProvider::ROUTE_REGISTRAR),
                        $container->get(AuthServiceProvider::ROUTE_REGISTRAR),
                        $container->get(AccountServiceProvider::ROUTE_REGISTRAR),
                        $container->get(SettingsServiceProvider::ROUTE_REGISTRAR),
                        $container->get(UsersServiceProvider::ROUTE_REGISTRAR),
                        $container->get(MetricsServiceProvider::ROUTE_REGISTRAR),
                        $container->get(PlacementsServiceProvider::ROUTE_REGISTRAR),
                        $container->get(CreativesServiceProvider::ROUTE_REGISTRAR),
                        $container->get(CreativesServiceProvider::REVIEW_ROUTE_REGISTRAR),
                        $container->get(MarketplaceServiceProvider::ROUTE_REGISTRAR),
                        $container->get(InvitationsServiceProvider::ROUTE_REGISTRAR),
                        $container->get(LegalHoldsServiceProvider::ROUTE_REGISTRAR),
                        $container->get(DsrServiceProvider::ROUTE_REGISTRAR),
                        $container->get(BillingServiceProvider::ROUTE_REGISTRAR),
                        $container->get(DealServiceProvider::ROUTE_REGISTRAR),
                        $container->get(AssetsServiceProvider::ROUTE_REGISTRAR),
                        $container->get(PublicServiceProvider::ROUTE_REGISTRAR),
                        $container->get(ServiceApiServiceProvider::ROUTE_REGISTRAR),
                    ];

                    foreach ($registrars as $registrar) {
                        if (!is_callable($registrar)) {
                            throw new LogicException('Route registrar service is invalid.');
                        }
                    }

                    return $registrars;
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    $handlers = [
                        $container->get(AuthServiceProvider::EXCEPTION_HANDLER),
                        $container->get(AuthServiceProvider::EXCEPTION_HANDLER_CONTEXT_REQUIRED),
                        $container->get(UsersServiceProvider::EXCEPTION_HANDLER),
                        $container->get(PlacementsServiceProvider::EXCEPTION_HANDLER_NOT_FOUND),
                        $container->get(PlacementsServiceProvider::EXCEPTION_HANDLER_VALIDATION),
                        $container->get(CreativesServiceProvider::EXCEPTION_HANDLER_NOT_FOUND),
                        $container->get(CreativesServiceProvider::EXCEPTION_HANDLER_TRANSITION),
                        $container->get(CreativesServiceProvider::EXCEPTION_HANDLER_SELF_APPROVAL),
                        $container->get(CreativesServiceProvider::EXCEPTION_HANDLER_SCAN),
                        $container->get(MarketplaceServiceProvider::EXCEPTION_HANDLER),
                        $container->get(InvitationsServiceProvider::EXCEPTION_HANDLER),
                        $container->get(LegalHoldsServiceProvider::EXCEPTION_HANDLER),
                        $container->get(BillingServiceProvider::EXCEPTION_HANDLER_NOT_FOUND),
                        $container->get(BillingServiceProvider::EXCEPTION_HANDLER_TRANSITION),
                        $container->get(BillingServiceProvider::EXCEPTION_HANDLER_RECONCILIATION),
                        $container->get(BillingServiceProvider::EXCEPTION_HANDLER_INVOICE_HANDOFF),
                        $container->get(DealServiceProvider::EXCEPTION_HANDLER_NOT_FOUND),
                        $container->get(DealServiceProvider::EXCEPTION_HANDLER_FAILED),
                        $container->get(AssetsServiceProvider::EXCEPTION_HANDLER_VALIDATION),
                        $container->get(AssetsServiceProvider::EXCEPTION_HANDLER_RECORDS),
                        $container->get(ServiceApiServiceProvider::EXCEPTION_HANDLER_MCP_VALIDATION),
                        $container->get(ServiceApiServiceProvider::EXCEPTION_HANDLER_PLAN_NOT_FOUND),
                        $container->get(ServiceApiServiceProvider::EXCEPTION_HANDLER_PLAN_STATE),
                        $container->get(SettingsServiceProvider::EXCEPTION_HANDLER_ENCRYPTION),
                        $container->get(SettingsServiceProvider::EXCEPTION_HANDLER_SMTP_NOT_CONFIGURED),
                        $container->get(SettingsServiceProvider::EXCEPTION_HANDLER_SMTP_TEST_FAILED),
                    ];

                    foreach ($handlers as $handler) {
                        if (!$handler instanceof DomainExceptionHandlerInterface) {
                            throw new LogicException('Exception handler service is invalid.');
                        }
                    }

                    return $handlers;
                },
            );
    }
}
