<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;

final readonly class DsrServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.dsr';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DataSubjectRequestUseCaseInterface::class,
                static fn (ContainerInterface $c): DataSubjectRequestUseCaseInterface => new DataSubjectRequestUseCase(
                    self::service($c, EventStoreInterface::class),
                    self::service($c, AuditLogInterface::class),
                    self::orgId($c),
                ),
            )
            ->set(
                DataSubjectRequestHandler::class,
                static fn (ContainerInterface $c): DataSubjectRequestHandler => new DataSubjectRequestHandler(self::service($c, DataSubjectRequestUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): DsrRouteRegistrar => new DsrRouteRegistrar(self::service($c, DataSubjectRequestHandler::class)),
            );
    }
}
