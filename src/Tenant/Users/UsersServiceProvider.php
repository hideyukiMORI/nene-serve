<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Settings\SmtpConfigResolver;
use NeneServe\Settings\SmtpSettingsRepositoryInterface;
use NeneServe\Support\Crypto;
use NeneServe\Support\ServiceProviderHelpers;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class UsersServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.users';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.user_validation';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ListUsersUseCaseInterface::class,
                static fn (ContainerInterface $c): ListUsersUseCaseInterface => new ListUsersUseCase(self::service($c, UserRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                ListUsersHandler::class,
                static fn (ContainerInterface $c): ListUsersHandler => new ListUsersHandler(self::service($c, ListUsersUseCaseInterface::class), self::json($c)),
            )
            ->set(
                CreateInvitedUserUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateInvitedUserUseCaseInterface => new CreateInvitedUserUseCase(
                    self::service($c, UserRepositoryInterface::class),
                    self::transactions($c),
                    self::orgId($c),
                    self::dialect($c),
                ),
            )
            ->set(
                SmtpConfigResolver::class,
                static fn (ContainerInterface $c): SmtpConfigResolver => new SmtpConfigResolver(
                    self::service($c, SmtpSettingsRepositoryInterface::class),
                    self::service($c, Crypto::class),
                ),
            )
            ->set(
                CreateUserHandler::class,
                static function (ContainerInterface $c): CreateUserHandler {
                    $appBaseUrl = getenv('APP_BASE_URL');

                    return new CreateUserHandler(
                        self::service($c, CreateInvitedUserUseCaseInterface::class),
                        self::service($c, SmtpConfigResolver::class),
                        self::service($c, MailerFactoryInterface::class),
                        self::json($c),
                        is_string($appBaseUrl) && $appBaseUrl !== '' ? $appBaseUrl : 'http://localhost:5189',
                    );
                },
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static fn (ContainerInterface $c): UserValidationExceptionHandler => new UserValidationExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): UsersRouteRegistrar => new UsersRouteRegistrar(
                    self::service($c, ListUsersHandler::class),
                    self::service($c, CreateUserHandler::class),
                ),
            );
    }
}
