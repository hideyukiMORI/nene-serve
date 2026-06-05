<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Support\ServiceProviderHelpers;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\PdoOrganizationRepository;
use NeneServe\Tenant\PdoUserRepository;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class AuthServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.auth';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.authentication_failed';

    public const string EXCEPTION_HANDLER_CONTEXT_REQUIRED = 'nene-serve.exception_handler.auth_context_required';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OrganizationRepositoryInterface::class,
                static fn (ContainerInterface $c): OrganizationRepositoryInterface => new PdoOrganizationRepository(self::query($c)),
            )
            ->set(
                UserRepositoryInterface::class,
                static fn (ContainerInterface $c): UserRepositoryInterface => new PdoUserRepository(self::query($c)),
            )
            ->set(
                LoginUseCaseInterface::class,
                static fn (ContainerInterface $c): LoginUseCaseInterface => new LoginUseCase(
                    self::service($c, OrganizationRepositoryInterface::class),
                    self::service($c, UserRepositoryInterface::class),
                    self::service($c, TokenIssuerInterface::class),
                ),
            )
            ->set(
                LoginHandler::class,
                static fn (ContainerInterface $c): LoginHandler => new LoginHandler(self::service($c, LoginUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static fn (ContainerInterface $c): AuthenticationFailedExceptionHandler => new AuthenticationFailedExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_CONTEXT_REQUIRED,
                static fn (ContainerInterface $c): AuthContextRequiredExceptionHandler => new AuthContextRequiredExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): AuthRouteRegistrar => new AuthRouteRegistrar(self::service($c, LoginHandler::class)),
            );
    }
}
