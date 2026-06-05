<?php

declare(strict_types=1);

namespace NeneServe\Health;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;

final readonly class HealthServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.health';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                HealthHandler::class,
                static fn (ContainerInterface $c): HealthHandler => new HealthHandler(self::json($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): HealthRouteRegistrar => new HealthRouteRegistrar(self::service($c, HealthHandler::class)),
            );
    }
}
