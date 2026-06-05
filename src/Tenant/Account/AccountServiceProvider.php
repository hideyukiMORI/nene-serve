<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Account;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Http\RuntimeServiceProvider;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Wires the authenticated-account endpoints (`GET /admin/me`). Reuses the
 * UserRepositoryInterface registered by {@see \NeneServe\Auth\AuthServiceProvider}.
 */
final readonly class AccountServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.account';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                GetCurrentUserUseCaseInterface::class,
                static function (ContainerInterface $container): GetCurrentUserUseCaseInterface {
                    $users = $container->get(UserRepositoryInterface::class);
                    $organizationId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('User repository service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new GetCurrentUserUseCase($users, $organizationId);
                },
            )
            ->set(
                CurrentUserHandler::class,
                static function (ContainerInterface $container): CurrentUserHandler {
                    $useCase = $container->get(GetCurrentUserUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$useCase instanceof GetCurrentUserUseCaseInterface) {
                        throw new LogicException('Get current user use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new CurrentUserHandler($useCase, $response, $problemDetails);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): AccountRouteRegistrar {
                    $currentUserHandler = $container->get(CurrentUserHandler::class);

                    if (!$currentUserHandler instanceof CurrentUserHandler) {
                        throw new LogicException('Current user handler service is invalid.');
                    }

                    return new AccountRouteRegistrar($currentUserHandler);
                },
            );
    }
}
