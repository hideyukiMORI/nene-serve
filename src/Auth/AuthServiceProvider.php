<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use LogicException;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\PdoOrganizationRepository;
use NeneServe\Tenant\PdoUserRepository;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class AuthServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.auth';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.authentication_failed';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OrganizationRepositoryInterface::class,
                static function (ContainerInterface $container): OrganizationRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoOrganizationRepository($query);
                },
            )
            ->set(
                UserRepositoryInterface::class,
                static function (ContainerInterface $container): UserRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoUserRepository($query);
                },
            )
            ->set(
                LoginUseCaseInterface::class,
                static function (ContainerInterface $container): LoginUseCaseInterface {
                    $organizations = $container->get(OrganizationRepositoryInterface::class);
                    $users = $container->get(UserRepositoryInterface::class);
                    $tokenIssuer = $container->get(TokenIssuerInterface::class);

                    if (!$organizations instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('User repository service is invalid.');
                    }

                    if (!$tokenIssuer instanceof TokenIssuerInterface) {
                        throw new LogicException('Token issuer service is invalid.');
                    }

                    return new LoginUseCase($organizations, $users, $tokenIssuer);
                },
            )
            ->set(
                LoginHandler::class,
                static function (ContainerInterface $container): LoginHandler {
                    $useCase = $container->get(LoginUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof LoginUseCaseInterface) {
                        throw new LogicException('Login use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new LoginHandler($useCase, $response);
                },
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static function (ContainerInterface $container): AuthenticationFailedExceptionHandler {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new AuthenticationFailedExceptionHandler($problemDetails);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): AuthRouteRegistrar {
                    $loginHandler = $container->get(LoginHandler::class);

                    if (!$loginHandler instanceof LoginHandler) {
                        throw new LogicException('Login handler service is invalid.');
                    }

                    return new AuthRouteRegistrar($loginHandler);
                },
            );
    }
}
