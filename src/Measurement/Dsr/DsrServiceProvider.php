<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Measurement\UseCase\DataSubjectRequestUseCase;
use Psr\Container\ContainerInterface;

final readonly class DsrServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.dsr';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DataSubjectRequestUseCase::class,
                static function (ContainerInterface $container): DataSubjectRequestUseCase {
                    $events = $container->get(EventStoreInterface::class);
                    $audit = $container->get(AuditLogInterface::class);

                    if (!$events instanceof EventStoreInterface) {
                        throw new LogicException('Event store service is invalid.');
                    }

                    if (!$audit instanceof AuditLogInterface) {
                        throw new LogicException('Audit log service is invalid.');
                    }

                    return new DataSubjectRequestUseCase($events, $audit);
                },
            )
            ->set(
                DataSubjectRequestHandler::class,
                static function (ContainerInterface $container): DataSubjectRequestHandler {
                    $useCase = $container->get(DataSubjectRequestUseCase::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$useCase instanceof DataSubjectRequestUseCase) {
                        throw new LogicException('Data subject request use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new DataSubjectRequestHandler($useCase, $response, $problemDetails);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): DsrRouteRegistrar {
                    $handler = $container->get(DataSubjectRequestHandler::class);

                    if (!$handler instanceof DataSubjectRequestHandler) {
                        throw new LogicException('Data subject request handler service is invalid.');
                    }

                    return new DsrRouteRegistrar($handler);
                },
            );
    }
}
