<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Mcp\Api\ApplyChangePlanUseCase;
use NeneServe\Mcp\Api\ApplyChangePlanUseCaseInterface;
use NeneServe\Mcp\Api\ProposePlacementChangeUseCase;
use NeneServe\Mcp\Api\ProposePlacementChangeUseCaseInterface;
use NeneServe\Measurement\Metrics\GetMetricsUseCase;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use NeneServe\Serving\Placements\ListPlacementsUseCaseInterface;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** Wires the service surface `/api/*` (scoped service-token auth, api-security §5). */
final readonly class ServiceApiServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.service';

    public const string EXCEPTION_HANDLER_MCP_VALIDATION = 'nene-serve.exception_handler.mcp_validation';

    public const string EXCEPTION_HANDLER_PLAN_NOT_FOUND = 'nene-serve.exception_handler.change_plan_not_found';

    public const string EXCEPTION_HANDLER_PLAN_STATE = 'nene-serve.exception_handler.invalid_plan_state';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ProposePlacementChangeUseCaseInterface::class,
                static fn (ContainerInterface $c): ProposePlacementChangeUseCaseInterface => new ProposePlacementChangeUseCase(self::query($c), self::transactions($c)),
            )
            ->set(
                ApplyChangePlanUseCaseInterface::class,
                static fn (ContainerInterface $c): ApplyChangePlanUseCaseInterface => new ApplyChangePlanUseCase(self::query($c), self::transactions($c)),
            )
            ->set(
                ListPlacementsHandler::class,
                static fn (ContainerInterface $c): ListPlacementsHandler => new ListPlacementsHandler(self::service($c, ListPlacementsUseCaseInterface::class), self::json($c)),
            )
            ->set(
                GetMetricsHandler::class,
                static fn (ContainerInterface $c): GetMetricsHandler => new GetMetricsHandler(self::service($c, GetMetricsUseCase::class), self::json($c), self::problem($c)),
            )
            ->set(
                ExportMetricsHandler::class,
                static fn (ContainerInterface $c): ExportMetricsHandler => new ExportMetricsHandler(
                    self::service($c, ExportMetricsUseCase::class),
                    self::service($c, ResponseFactoryInterface::class),
                    self::service($c, StreamFactoryInterface::class),
                    self::problem($c),
                ),
            )
            ->set(
                ProposeChangeHandler::class,
                static fn (ContainerInterface $c): ProposeChangeHandler => new ProposeChangeHandler(self::service($c, ProposePlacementChangeUseCaseInterface::class), self::json($c), self::problem($c)),
            )
            ->set(
                ApplyChangeHandler::class,
                static fn (ContainerInterface $c): ApplyChangeHandler => new ApplyChangeHandler(self::service($c, ApplyChangePlanUseCaseInterface::class), self::json($c), self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_MCP_VALIDATION,
                static fn (ContainerInterface $c): McpValidationExceptionHandler => new McpValidationExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_PLAN_NOT_FOUND,
                static fn (ContainerInterface $c): ChangePlanNotFoundExceptionHandler => new ChangePlanNotFoundExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_PLAN_STATE,
                static fn (ContainerInterface $c): InvalidPlanStateExceptionHandler => new InvalidPlanStateExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): ServiceRouteRegistrar => new ServiceRouteRegistrar(
                    self::service($c, ListPlacementsHandler::class),
                    self::service($c, GetMetricsHandler::class),
                    self::service($c, ExportMetricsHandler::class),
                    self::service($c, ProposeChangeHandler::class),
                    self::service($c, ApplyChangeHandler::class),
                ),
            );
    }
}
