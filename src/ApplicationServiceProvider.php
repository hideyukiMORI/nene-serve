<?php

declare(strict_types=1);

namespace NeneServe;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use NeneServe\Auth\AuthenticationFailedExceptionHandler;
use NeneServe\Auth\AuthRouteRegistrar;
use NeneServe\Auth\AuthServiceProvider;
use NeneServe\Health\HealthRouteRegistrar;
use NeneServe\Health\HealthServiceProvider;
use NeneServe\Marketplace\Admin\MarketplaceRouteRegistrar;
use NeneServe\Marketplace\Admin\MarketplaceServiceProvider;
use NeneServe\Marketplace\Admin\MarketplaceValidationExceptionHandler;
use NeneServe\Measurement\Dsr\DsrRouteRegistrar;
use NeneServe\Measurement\Dsr\DsrServiceProvider;
use NeneServe\Measurement\Metrics\MetricsRouteRegistrar;
use NeneServe\Measurement\Metrics\MetricsServiceProvider;
use NeneServe\Retention\LegalHolds\LegalHoldExceptionHandler;
use NeneServe\Retention\LegalHolds\LegalHoldsRouteRegistrar;
use NeneServe\Retention\LegalHolds\LegalHoldsServiceProvider;
use NeneServe\Serving\Creatives\CreativeNotFoundExceptionHandler;
use NeneServe\Serving\Creatives\CreativeReviewRouteRegistrar;
use NeneServe\Serving\Creatives\CreativeScanFailedExceptionHandler;
use NeneServe\Serving\Creatives\CreativesRouteRegistrar;
use NeneServe\Serving\Creatives\CreativesServiceProvider;
use NeneServe\Serving\Creatives\InvalidReviewTransitionExceptionHandler;
use NeneServe\Serving\Creatives\SelfApprovalForbiddenExceptionHandler;
use NeneServe\Serving\Placements\CreativeValidationExceptionHandler;
use NeneServe\Serving\Placements\PlacementNotFoundExceptionHandler;
use NeneServe\Serving\Placements\PlacementsRouteRegistrar;
use NeneServe\Serving\Placements\PlacementsServiceProvider;
use NeneServe\Settings\SettingsRouteRegistrar;
use NeneServe\Settings\SettingsServiceProvider;
use NeneServe\Tenant\Account\AccountRouteRegistrar;
use NeneServe\Tenant\Account\AccountServiceProvider;
use NeneServe\Tenant\Invitations\InvitationInvalidExceptionHandler;
use NeneServe\Tenant\Invitations\InvitationsRouteRegistrar;
use NeneServe\Tenant\Invitations\InvitationsServiceProvider;
use NeneServe\Tenant\Users\UsersRouteRegistrar;
use NeneServe\Tenant\Users\UsersServiceProvider;
use NeneServe\Tenant\Users\UserValidationExceptionHandler;
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
            ->addProvider(new DsrServiceProvider());

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $health = $container->get(HealthServiceProvider::ROUTE_REGISTRAR);
                    $auth = $container->get(AuthServiceProvider::ROUTE_REGISTRAR);
                    $account = $container->get(AccountServiceProvider::ROUTE_REGISTRAR);
                    $settings = $container->get(SettingsServiceProvider::ROUTE_REGISTRAR);
                    $users = $container->get(UsersServiceProvider::ROUTE_REGISTRAR);
                    $metrics = $container->get(MetricsServiceProvider::ROUTE_REGISTRAR);
                    $placements = $container->get(PlacementsServiceProvider::ROUTE_REGISTRAR);
                    $creatives = $container->get(CreativesServiceProvider::ROUTE_REGISTRAR);
                    $creativeReview = $container->get(CreativesServiceProvider::REVIEW_ROUTE_REGISTRAR);
                    $marketplace = $container->get(MarketplaceServiceProvider::ROUTE_REGISTRAR);
                    $invitations = $container->get(InvitationsServiceProvider::ROUTE_REGISTRAR);
                    $legalHolds = $container->get(LegalHoldsServiceProvider::ROUTE_REGISTRAR);
                    $dsr = $container->get(DsrServiceProvider::ROUTE_REGISTRAR);

                    if (!$health instanceof HealthRouteRegistrar) {
                        throw new LogicException('Health route registrar service is invalid.');
                    }

                    if (!$auth instanceof AuthRouteRegistrar) {
                        throw new LogicException('Auth route registrar service is invalid.');
                    }

                    if (!$account instanceof AccountRouteRegistrar) {
                        throw new LogicException('Account route registrar service is invalid.');
                    }

                    if (!$settings instanceof SettingsRouteRegistrar) {
                        throw new LogicException('Settings route registrar service is invalid.');
                    }

                    if (!$users instanceof UsersRouteRegistrar) {
                        throw new LogicException('Users route registrar service is invalid.');
                    }

                    if (!$metrics instanceof MetricsRouteRegistrar) {
                        throw new LogicException('Metrics route registrar service is invalid.');
                    }

                    if (!$placements instanceof PlacementsRouteRegistrar) {
                        throw new LogicException('Placements route registrar service is invalid.');
                    }

                    if (!$creatives instanceof CreativesRouteRegistrar) {
                        throw new LogicException('Creatives route registrar service is invalid.');
                    }

                    if (!$creativeReview instanceof CreativeReviewRouteRegistrar) {
                        throw new LogicException('Creative review route registrar service is invalid.');
                    }

                    if (!$marketplace instanceof MarketplaceRouteRegistrar) {
                        throw new LogicException('Marketplace route registrar service is invalid.');
                    }

                    if (!$invitations instanceof InvitationsRouteRegistrar) {
                        throw new LogicException('Invitations route registrar service is invalid.');
                    }

                    if (!$legalHolds instanceof LegalHoldsRouteRegistrar) {
                        throw new LogicException('Legal holds route registrar service is invalid.');
                    }

                    if (!$dsr instanceof DsrRouteRegistrar) {
                        throw new LogicException('DSR route registrar service is invalid.');
                    }

                    /** @var list<callable(\Nene2\Routing\Router): void> $registrars */
                    $registrars = [$health, $auth, $account, $settings, $users, $metrics, $placements, $creatives, $creativeReview, $marketplace, $invitations, $legalHolds, $dsr];

                    return $registrars;
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    $authenticationFailed = $container->get(AuthServiceProvider::EXCEPTION_HANDLER);
                    $userValidation = $container->get(UsersServiceProvider::EXCEPTION_HANDLER);
                    $placementNotFound = $container->get(PlacementsServiceProvider::EXCEPTION_HANDLER_NOT_FOUND);
                    $placementValidation = $container->get(PlacementsServiceProvider::EXCEPTION_HANDLER_VALIDATION);

                    if (!$authenticationFailed instanceof AuthenticationFailedExceptionHandler) {
                        throw new LogicException('Authentication failed exception handler service is invalid.');
                    }

                    if (!$userValidation instanceof UserValidationExceptionHandler) {
                        throw new LogicException('User validation exception handler service is invalid.');
                    }

                    if (!$placementNotFound instanceof PlacementNotFoundExceptionHandler) {
                        throw new LogicException('Placement not found exception handler service is invalid.');
                    }

                    if (!$placementValidation instanceof CreativeValidationExceptionHandler) {
                        throw new LogicException('Placement validation exception handler service is invalid.');
                    }

                    $creativeNotFound = $container->get(CreativesServiceProvider::EXCEPTION_HANDLER_NOT_FOUND);
                    $invalidTransition = $container->get(CreativesServiceProvider::EXCEPTION_HANDLER_TRANSITION);
                    $selfApproval = $container->get(CreativesServiceProvider::EXCEPTION_HANDLER_SELF_APPROVAL);
                    $scanFailed = $container->get(CreativesServiceProvider::EXCEPTION_HANDLER_SCAN);

                    if (!$creativeNotFound instanceof CreativeNotFoundExceptionHandler) {
                        throw new LogicException('Creative not found exception handler service is invalid.');
                    }

                    if (!$invalidTransition instanceof InvalidReviewTransitionExceptionHandler) {
                        throw new LogicException('Invalid review transition exception handler service is invalid.');
                    }

                    if (!$selfApproval instanceof SelfApprovalForbiddenExceptionHandler) {
                        throw new LogicException('Self-approval exception handler service is invalid.');
                    }

                    if (!$scanFailed instanceof CreativeScanFailedExceptionHandler) {
                        throw new LogicException('Creative scan failed exception handler service is invalid.');
                    }

                    $marketplaceValidation = $container->get(MarketplaceServiceProvider::EXCEPTION_HANDLER);
                    $invitationInvalid = $container->get(InvitationsServiceProvider::EXCEPTION_HANDLER);
                    $legalHold = $container->get(LegalHoldsServiceProvider::EXCEPTION_HANDLER);

                    if (!$marketplaceValidation instanceof MarketplaceValidationExceptionHandler) {
                        throw new LogicException('Marketplace validation exception handler service is invalid.');
                    }

                    if (!$invitationInvalid instanceof InvitationInvalidExceptionHandler) {
                        throw new LogicException('Invitation invalid exception handler service is invalid.');
                    }

                    if (!$legalHold instanceof LegalHoldExceptionHandler) {
                        throw new LogicException('Legal hold exception handler service is invalid.');
                    }

                    /** @var list<DomainExceptionHandlerInterface> $handlers */
                    $handlers = [
                        $authenticationFailed,
                        $userValidation,
                        $placementNotFound,
                        $placementValidation,
                        $creativeNotFound,
                        $invalidTransition,
                        $selfApproval,
                        $scanFailed,
                        $marketplaceValidation,
                        $invitationInvalid,
                        $legalHold,
                    ];

                    return $handlers;
                },
            );
    }
}
