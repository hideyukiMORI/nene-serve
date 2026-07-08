<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Metrics;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class MetricsServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.metrics';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                GetMetricsUseCase::class,
                static fn (ContainerInterface $c): GetMetricsUseCase => new GetMetricsUseCase(
                    self::service($c, EventStoreInterface::class),
                    self::transactions($c),
                    self::orgId($c),
                ),
            )
            ->set(
                ExportMetricsUseCase::class,
                static fn (ContainerInterface $c): ExportMetricsUseCase => new ExportMetricsUseCase(self::service($c, EventStoreInterface::class), self::orgId($c)),
            )
            ->set(
                GetMetricsHandler::class,
                static fn (ContainerInterface $c): GetMetricsHandler => new GetMetricsHandler(self::service($c, GetMetricsUseCase::class), self::json($c), self::problem($c), self::clock($c)),
            )
            ->set(
                ExportMetricsHandler::class,
                static fn (ContainerInterface $c): ExportMetricsHandler => new ExportMetricsHandler(
                    self::service($c, ExportMetricsUseCase::class),
                    self::service($c, ResponseFactoryInterface::class),
                    self::service($c, StreamFactoryInterface::class),
                    self::clock($c),
                ),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): MetricsRouteRegistrar => new MetricsRouteRegistrar(
                    self::service($c, GetMetricsHandler::class),
                    self::service($c, ExportMetricsHandler::class),
                ),
            );
    }
}
