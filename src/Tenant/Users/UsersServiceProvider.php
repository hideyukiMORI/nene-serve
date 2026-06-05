<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\UseCase\ListUsersUseCase;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class UsersServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.users';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ListUsersUseCase::class,
                static function (ContainerInterface $container): ListUsersUseCase {
                    $users = $container->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('User repository service is invalid.');
                    }

                    return new ListUsersUseCase($users);
                },
            )
            ->set(
                ListUsersHandler::class,
                static function (ContainerInterface $container): ListUsersHandler {
                    $listUsers = $container->get(ListUsersUseCase::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$listUsers instanceof ListUsersUseCase) {
                        throw new LogicException('List users use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new ListUsersHandler($listUsers, $response, $problemDetails);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): UsersRouteRegistrar {
                    $listHandler = $container->get(ListUsersHandler::class);

                    if (!$listHandler instanceof ListUsersHandler) {
                        throw new LogicException('List users handler service is invalid.');
                    }

                    return new UsersRouteRegistrar($listHandler);
                },
            );
    }
}
