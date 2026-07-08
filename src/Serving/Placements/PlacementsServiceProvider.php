<?php

declare(strict_types=1);

namespace NeneServe\Serving\Placements;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Serving\PdoPlacementRepository;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;

final readonly class PlacementsServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.placements';

    public const string EXCEPTION_HANDLER_NOT_FOUND = 'nene-serve.exception_handler.placement_not_found';

    public const string EXCEPTION_HANDLER_VALIDATION = 'nene-serve.exception_handler.placement_validation';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                PlacementRepositoryInterface::class,
                static fn (ContainerInterface $c): PlacementRepositoryInterface => new PdoPlacementRepository(self::query($c)),
            )
            ->set(
                ListPlacementsUseCaseInterface::class,
                static fn (ContainerInterface $c): ListPlacementsUseCaseInterface => new ListPlacementsUseCase(self::service($c, PlacementRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                GetPlacementByIdUseCaseInterface::class,
                static fn (ContainerInterface $c): GetPlacementByIdUseCaseInterface => new GetPlacementByIdUseCase(self::service($c, PlacementRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                CreatePlacementUseCaseInterface::class,
                static fn (ContainerInterface $c): CreatePlacementUseCaseInterface => new CreatePlacementUseCase(self::transactions($c), self::orgId($c), self::dialect($c)),
            )
            ->set(
                ArchivePlacementUseCaseInterface::class,
                static fn (ContainerInterface $c): ArchivePlacementUseCaseInterface => new ArchivePlacementUseCase(self::service($c, PlacementRepositoryInterface::class), self::transactions($c), self::orgId($c), self::clock($c)),
            )
            ->set(
                ListPlacementsHandler::class,
                static fn (ContainerInterface $c): ListPlacementsHandler => new ListPlacementsHandler(self::service($c, ListPlacementsUseCaseInterface::class), self::json($c)),
            )
            ->set(
                GetPlacementHandler::class,
                static fn (ContainerInterface $c): GetPlacementHandler => new GetPlacementHandler(self::service($c, GetPlacementByIdUseCaseInterface::class), self::json($c)),
            )
            ->set(
                CreatePlacementHandler::class,
                static fn (ContainerInterface $c): CreatePlacementHandler => new CreatePlacementHandler(self::service($c, CreatePlacementUseCaseInterface::class), self::json($c)),
            )
            ->set(
                ArchivePlacementHandler::class,
                static fn (ContainerInterface $c): ArchivePlacementHandler => new ArchivePlacementHandler(self::service($c, ArchivePlacementUseCaseInterface::class), self::json($c)),
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
                static fn (ContainerInterface $c): PlacementsRouteRegistrar => new PlacementsRouteRegistrar(
                    self::service($c, ListPlacementsHandler::class),
                    self::service($c, GetPlacementHandler::class),
                    self::service($c, CreatePlacementHandler::class),
                    self::service($c, ArchivePlacementHandler::class),
                ),
            );
    }
}
