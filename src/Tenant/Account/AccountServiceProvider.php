<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Account;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Support\ServiceProviderHelpers;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Wires the authenticated-account endpoints (`GET /admin/me`). Reuses the
 * UserRepositoryInterface registered by {@see \NeneServe\Auth\AuthServiceProvider}.
 */
final readonly class AccountServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.account';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                GetCurrentUserUseCaseInterface::class,
                static fn (ContainerInterface $c): GetCurrentUserUseCaseInterface => new GetCurrentUserUseCase(self::service($c, UserRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                CurrentUserHandler::class,
                static fn (ContainerInterface $c): CurrentUserHandler => new CurrentUserHandler(self::service($c, GetCurrentUserUseCaseInterface::class), self::json($c), self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): AccountRouteRegistrar => new AccountRouteRegistrar(self::service($c, CurrentUserHandler::class)),
            );
    }
}
