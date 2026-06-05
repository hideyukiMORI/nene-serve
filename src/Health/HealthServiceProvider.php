<?php

declare(strict_types=1);

namespace NeneServe\Health;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class HealthServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.health';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                HealthHandler::class,
                static function (ContainerInterface $container): HealthHandler {
                    $jsonResponses = $container->get(JsonResponseFactory::class);

                    if (!$jsonResponses instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new HealthHandler($jsonResponses);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): HealthRouteRegistrar {
                    $handler = $container->get(HealthHandler::class);

                    if (!$handler instanceof HealthHandler) {
                        throw new LogicException('Health handler service is invalid.');
                    }

                    return new HealthRouteRegistrar($handler);
                },
            );
    }
}
