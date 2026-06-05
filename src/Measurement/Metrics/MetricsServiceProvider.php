<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Metrics;

use LogicException;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Http\RuntimeServiceProvider;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class MetricsServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.metrics';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                GetMetricsUseCase::class,
                static function (ContainerInterface $container): GetMetricsUseCase {
                    $events = $container->get(EventStoreInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);
                    $organizationId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$events instanceof EventStoreInterface) {
                        throw new LogicException('Event store service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new GetMetricsUseCase($events, $transactions, $organizationId);
                },
            )
            ->set(
                ExportMetricsUseCase::class,
                static function (ContainerInterface $container): ExportMetricsUseCase {
                    $events = $container->get(EventStoreInterface::class);
                    $organizationId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$events instanceof EventStoreInterface) {
                        throw new LogicException('Event store service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new ExportMetricsUseCase($events, $organizationId);
                },
            )
            ->set(
                GetMetricsHandler::class,
                static function (ContainerInterface $container): GetMetricsHandler {
                    $metrics = $container->get(GetMetricsUseCase::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$metrics instanceof GetMetricsUseCase) {
                        throw new LogicException('Get metrics use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new GetMetricsHandler($metrics, $response, $problemDetails);
                },
            )
            ->set(
                ExportMetricsHandler::class,
                static function (ContainerInterface $container): ExportMetricsHandler {
                    $export = $container->get(ExportMetricsUseCase::class);
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);

                    if (!$export instanceof ExportMetricsUseCase) {
                        throw new LogicException('Export metrics use case service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    return new ExportMetricsHandler($export, $responseFactory, $streamFactory);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): MetricsRouteRegistrar {
                    $getHandler = $container->get(GetMetricsHandler::class);
                    $exportHandler = $container->get(ExportMetricsHandler::class);

                    if (!$getHandler instanceof GetMetricsHandler) {
                        throw new LogicException('Get metrics handler service is invalid.');
                    }

                    if (!$exportHandler instanceof ExportMetricsHandler) {
                        throw new LogicException('Export metrics handler service is invalid.');
                    }

                    return new MetricsRouteRegistrar($getHandler, $exportHandler);
                },
            );
    }
}
