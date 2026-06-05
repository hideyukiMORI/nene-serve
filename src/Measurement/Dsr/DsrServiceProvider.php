<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Http\RuntimeServiceProvider;
use NeneServe\Measurement\EventStoreInterface;
use Psr\Container\ContainerInterface;

final readonly class DsrServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.dsr';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DataSubjectRequestUseCaseInterface::class,
                static function (ContainerInterface $container): DataSubjectRequestUseCaseInterface {
                    $events = $container->get(EventStoreInterface::class);
                    $audit = $container->get(AuditLogInterface::class);
                    $organizationId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$events instanceof EventStoreInterface) {
                        throw new LogicException('Event store service is invalid.');
                    }

                    if (!$audit instanceof AuditLogInterface) {
                        throw new LogicException('Audit log service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new DataSubjectRequestUseCase($events, $audit, $organizationId);
                },
            )
            ->set(
                DataSubjectRequestHandler::class,
                static function (ContainerInterface $container): DataSubjectRequestHandler {
                    $useCase = $container->get(DataSubjectRequestUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    if (!$useCase instanceof DataSubjectRequestUseCaseInterface) {
                        throw new LogicException('Data subject request use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DataSubjectRequestHandler($useCase, $response);
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
