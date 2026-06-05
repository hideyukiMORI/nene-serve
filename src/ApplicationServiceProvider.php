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
            ->addProvider(new AuthServiceProvider());

        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $health = $container->get(HealthServiceProvider::ROUTE_REGISTRAR);
                    $auth = $container->get(AuthServiceProvider::ROUTE_REGISTRAR);

                    if (!$health instanceof HealthRouteRegistrar) {
                        throw new LogicException('Health route registrar service is invalid.');
                    }

                    if (!$auth instanceof AuthRouteRegistrar) {
                        throw new LogicException('Auth route registrar service is invalid.');
                    }

                    /** @var list<callable(\Nene2\Routing\Router): void> $registrars */
                    $registrars = [$health, $auth];

                    return $registrars;
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    $authenticationFailed = $container->get(AuthServiceProvider::EXCEPTION_HANDLER);

                    if (!$authenticationFailed instanceof AuthenticationFailedExceptionHandler) {
                        throw new LogicException('Authentication failed exception handler service is invalid.');
                    }

                    /** @var list<DomainExceptionHandlerInterface> $handlers */
                    $handlers = [$authenticationFailed];

                    return $handlers;
                },
            );
    }
}
