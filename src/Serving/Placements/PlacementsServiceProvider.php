<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\PlacementRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class PlacementsServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.placements';

    public const string EXCEPTION_HANDLER_NOT_FOUND = 'nene-serve.exception_handler.placement_not_found';

    public const string EXCEPTION_HANDLER_VALIDATION = 'nene-serve.exception_handler.placement_validation';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                PlacementRepositoryInterface::class,
                static function (ContainerInterface $container): PlacementRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoPlacementRepository($query);
                },
            )
            ->set(
                CreatePlacementUseCaseInterface::class,
                static function (ContainerInterface $container): CreatePlacementUseCaseInterface {
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    return new CreatePlacementUseCase($transactions);
                },
            )
            ->set(
                ArchivePlacementUseCaseInterface::class,
                static function (ContainerInterface $container): ArchivePlacementUseCaseInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    return new ArchivePlacementUseCase($query, $transactions);
                },
            )
            ->set(
                ListPlacementsHandler::class,
                static fn (ContainerInterface $c): ListPlacementsHandler => new ListPlacementsHandler(
                    self::placements($c),
                    self::json($c),
                    self::problem($c),
                ),
            )
            ->set(
                GetPlacementHandler::class,
                static fn (ContainerInterface $c): GetPlacementHandler => new GetPlacementHandler(
                    self::placements($c),
                    self::json($c),
                    self::problem($c),
                ),
            )
            ->set(
                CreatePlacementHandler::class,
                static function (ContainerInterface $container): CreatePlacementHandler {
                    $useCase = $container->get(CreatePlacementUseCaseInterface::class);

                    if (!$useCase instanceof CreatePlacementUseCaseInterface) {
                        throw new LogicException('Create placement use case service is invalid.');
                    }

                    return new CreatePlacementHandler($useCase, self::json($container), self::problem($container));
                },
            )
            ->set(
                ArchivePlacementHandler::class,
                static function (ContainerInterface $container): ArchivePlacementHandler {
                    $useCase = $container->get(ArchivePlacementUseCaseInterface::class);

                    if (!$useCase instanceof ArchivePlacementUseCaseInterface) {
                        throw new LogicException('Archive placement use case service is invalid.');
                    }

                    return new ArchivePlacementHandler($useCase, self::json($container), self::problem($container));
                },
            )
            ->set(
                self::EXCEPTION_HANDLER_NOT_FOUND,
                static fn (ContainerInterface $c): PlacementNotFoundExceptionHandler => new PlacementNotFoundExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_VALIDATION,
                static fn (ContainerInterface $c): CreativeValidationExceptionHandler => new CreativeValidationExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): PlacementsRouteRegistrar {
                    $list = $container->get(ListPlacementsHandler::class);
                    $get = $container->get(GetPlacementHandler::class);
                    $create = $container->get(CreatePlacementHandler::class);
                    $archive = $container->get(ArchivePlacementHandler::class);

                    if (!$list instanceof ListPlacementsHandler) {
                        throw new LogicException('List placements handler service is invalid.');
                    }

                    if (!$get instanceof GetPlacementHandler) {
                        throw new LogicException('Get placement handler service is invalid.');
                    }

                    if (!$create instanceof CreatePlacementHandler) {
                        throw new LogicException('Create placement handler service is invalid.');
                    }

                    if (!$archive instanceof ArchivePlacementHandler) {
                        throw new LogicException('Archive placement handler service is invalid.');
                    }

                    return new PlacementsRouteRegistrar($list, $get, $create, $archive);
                },
            );
    }

    private static function placements(ContainerInterface $container): PlacementRepositoryInterface
    {
        $placements = $container->get(PlacementRepositoryInterface::class);

        if (!$placements instanceof PlacementRepositoryInterface) {
            throw new LogicException('Placement repository service is invalid.');
        }

        return $placements;
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
}
