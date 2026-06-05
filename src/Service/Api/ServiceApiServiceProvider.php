<?php

declare(strict_types=1);

namespace NeneServe\Service\Api;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Mcp\Api\ApplyChangePlanUseCase;
use NeneServe\Mcp\Api\ApplyChangePlanUseCaseInterface;
use NeneServe\Mcp\Api\ProposePlacementChangeUseCase;
use NeneServe\Mcp\Api\ProposePlacementChangeUseCaseInterface;
use NeneServe\Measurement\Metrics\GetMetricsUseCase;
use NeneServe\Measurement\UseCase\ExportMetricsUseCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** Wires the service surface `/api/*` (scoped service-token auth, api-security §5). */
final readonly class ServiceApiServiceProvider implements ServiceProviderInterface
{
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
                static fn (ContainerInterface $c): ListPlacementsHandler => new ListPlacementsHandler(self::query($c), self::json($c), self::problem($c)),
            )
            ->set(
                GetMetricsHandler::class,
                static function (ContainerInterface $c): GetMetricsHandler {
                    $metrics = $c->get(GetMetricsUseCase::class);

                    if (!$metrics instanceof GetMetricsUseCase) {
                        throw new LogicException('Get metrics use case service is invalid.');
                    }

                    return new GetMetricsHandler($metrics, self::json($c), self::problem($c));
                },
            )
            ->set(
                ExportMetricsHandler::class,
                static function (ContainerInterface $c): ExportMetricsHandler {
                    $export = $c->get(ExportMetricsUseCase::class);

                    if (!$export instanceof ExportMetricsUseCase) {
                        throw new LogicException('Export metrics use case service is invalid.');
                    }

                    return new ExportMetricsHandler($export, self::responseFactory($c), self::streamFactory($c), self::problem($c));
                },
            )
            ->set(
                ProposeChangeHandler::class,
                static function (ContainerInterface $c): ProposeChangeHandler {
                    $useCase = $c->get(ProposePlacementChangeUseCaseInterface::class);

                    if (!$useCase instanceof ProposePlacementChangeUseCaseInterface) {
                        throw new LogicException('Propose placement change use case service is invalid.');
                    }

                    return new ProposeChangeHandler($useCase, self::json($c), self::problem($c));
                },
            )
            ->set(
                ApplyChangeHandler::class,
                static function (ContainerInterface $c): ApplyChangeHandler {
                    $useCase = $c->get(ApplyChangePlanUseCaseInterface::class);

                    if (!$useCase instanceof ApplyChangePlanUseCaseInterface) {
                        throw new LogicException('Apply change plan use case service is invalid.');
                    }

                    return new ApplyChangeHandler($useCase, self::json($c), self::problem($c));
                },
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
                static function (ContainerInterface $c): ServiceRouteRegistrar {
                    $list = $c->get(ListPlacementsHandler::class);
                    $getMetrics = $c->get(GetMetricsHandler::class);
                    $exportMetrics = $c->get(ExportMetricsHandler::class);
                    $propose = $c->get(ProposeChangeHandler::class);
                    $apply = $c->get(ApplyChangeHandler::class);

                    if (!$list instanceof ListPlacementsHandler) {
                        throw new LogicException('List placements handler service is invalid.');
                    }

                    if (!$getMetrics instanceof GetMetricsHandler) {
                        throw new LogicException('Get metrics handler service is invalid.');
                    }

                    if (!$exportMetrics instanceof ExportMetricsHandler) {
                        throw new LogicException('Export metrics handler service is invalid.');
                    }

                    if (!$propose instanceof ProposeChangeHandler) {
                        throw new LogicException('Propose change handler service is invalid.');
                    }

                    if (!$apply instanceof ApplyChangeHandler) {
                        throw new LogicException('Apply change handler service is invalid.');
                    }

                    return new ServiceRouteRegistrar($list, $getMetrics, $exportMetrics, $propose, $apply);
                },
            );
    }

    private static function query(ContainerInterface $container): DatabaseQueryExecutorInterface
    {
        $query = $container->get(DatabaseQueryExecutorInterface::class);

        if (!$query instanceof DatabaseQueryExecutorInterface) {
            throw new LogicException('Database query executor service is invalid.');
        }

        return $query;
    }

    private static function transactions(ContainerInterface $container): DatabaseTransactionManagerInterface
    {
        $transactions = $container->get(DatabaseTransactionManagerInterface::class);

        if (!$transactions instanceof DatabaseTransactionManagerInterface) {
            throw new LogicException('Database transaction manager service is invalid.');
        }

        return $transactions;
    }

    private static function json(ContainerInterface $container): JsonResponseFactory
    {
        $json = $container->get(JsonResponseFactory::class);

        if (!$json instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $json;
    }

    private static function problem(ContainerInterface $container): ProblemDetailsResponseFactory
    {
        $problem = $container->get(ProblemDetailsResponseFactory::class);

        if (!$problem instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $problem;
    }

    private static function responseFactory(ContainerInterface $container): ResponseFactoryInterface
    {
        $factory = $container->get(ResponseFactoryInterface::class);

        if (!$factory instanceof ResponseFactoryInterface) {
            throw new LogicException('Response factory service is invalid.');
        }

        return $factory;
    }

    private static function streamFactory(ContainerInterface $container): StreamFactoryInterface
    {
        $factory = $container->get(StreamFactoryInterface::class);

        if (!$factory instanceof StreamFactoryInterface) {
            throw new LogicException('Stream factory service is invalid.');
        }

        return $factory;
    }
}
