<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use LogicException;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Http\RuntimeServiceProvider;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Settings\SmtpConfigResolver;
use NeneServe\Settings\SmtpSettingsRepositoryInterface;
use NeneServe\Support\Crypto;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class UsersServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.users';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.user_validation';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ListUsersUseCaseInterface::class,
                static function (ContainerInterface $container): ListUsersUseCaseInterface {
                    $users = $container->get(UserRepositoryInterface::class);
                    $organizationId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('User repository service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new ListUsersUseCase($users, $organizationId);
                },
            )
            ->set(
                ListUsersHandler::class,
                static function (ContainerInterface $container): ListUsersHandler {
                    $listUsers = $container->get(ListUsersUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$listUsers instanceof ListUsersUseCaseInterface) {
                        throw new LogicException('List users use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListUsersHandler($listUsers, $response);
                },
            )
            ->set(
                CreateInvitedUserUseCaseInterface::class,
                static function (ContainerInterface $container): CreateInvitedUserUseCaseInterface {
                    $users = $container->get(UserRepositoryInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);
                    $organizationId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('User repository service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new CreateInvitedUserUseCase($users, $transactions, $organizationId);
                },
            )
            ->set(
                SmtpConfigResolver::class,
                static function (ContainerInterface $container): SmtpConfigResolver {
                    $settings = $container->get(SmtpSettingsRepositoryInterface::class);
                    $crypto = $container->get(Crypto::class);

                    if (!$settings instanceof SmtpSettingsRepositoryInterface) {
                        throw new LogicException('SMTP settings repository service is invalid.');
                    }

                    if (!$crypto instanceof Crypto) {
                        throw new LogicException('Crypto service is invalid.');
                    }

                    return new SmtpConfigResolver($settings, $crypto);
                },
            )
            ->set(
                CreateUserHandler::class,
                static function (ContainerInterface $container): CreateUserHandler {
                    $createUser = $container->get(CreateInvitedUserUseCaseInterface::class);
                    $smtp = $container->get(SmtpConfigResolver::class);
                    $mailerFactory = $container->get(MailerFactoryInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$createUser instanceof CreateInvitedUserUseCaseInterface) {
                        throw new LogicException('Create invited user use case service is invalid.');
                    }

                    if (!$smtp instanceof SmtpConfigResolver) {
                        throw new LogicException('SMTP config resolver service is invalid.');
                    }

                    if (!$mailerFactory instanceof MailerFactoryInterface) {
                        throw new LogicException('Mailer factory service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    $appBaseUrl = getenv('APP_BASE_URL');

                    return new CreateUserHandler(
                        $createUser,
                        $smtp,
                        $mailerFactory,
                        $response,
                        is_string($appBaseUrl) && $appBaseUrl !== '' ? $appBaseUrl : 'http://localhost:5189',
                    );
                },
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static function (ContainerInterface $container): UserValidationExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new UserValidationExceptionHandler($problemDetails);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): UsersRouteRegistrar {
                    $listHandler = $container->get(ListUsersHandler::class);
                    $createHandler = $container->get(CreateUserHandler::class);

                    if (!$listHandler instanceof ListUsersHandler) {
                        throw new LogicException('List users handler service is invalid.');
                    }

                    if (!$createHandler instanceof CreateUserHandler) {
                        throw new LogicException('Create user handler service is invalid.');
                    }

                    return new UsersRouteRegistrar($listHandler, $createHandler);
                },
            );
    }
}
