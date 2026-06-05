<?php

declare(strict_types=1);

namespace NeneServe;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
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
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    /** @var list<callable(\Nene2\Routing\Router): void> $registrars */
                    $registrars = [];

                    return $registrars;
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    /** @var list<\Nene2\Error\DomainExceptionHandlerInterface> $handlers */
                    $handlers = [];

                    return $handlers;
                },
            );
    }
}
