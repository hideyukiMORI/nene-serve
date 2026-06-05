<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Retention\LegalHoldRepositoryInterface;
use NeneServe\Retention\PdoLegalHoldRepository;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;

final readonly class LegalHoldsServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.legal_holds';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.legal_hold';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                LegalHoldRepositoryInterface::class,
                static fn (ContainerInterface $c): LegalHoldRepositoryInterface => new PdoLegalHoldRepository(self::query($c)),
            )
            ->set(
                LegalHoldUseCaseInterface::class,
                static fn (ContainerInterface $c): LegalHoldUseCaseInterface => new LegalHoldUseCase(
                    self::service($c, LegalHoldRepositoryInterface::class),
                    self::transactions($c),
                    self::orgId($c),
                ),
            )
            ->set(
                PlaceLegalHoldHandler::class,
                static fn (ContainerInterface $c): PlaceLegalHoldHandler => new PlaceLegalHoldHandler(self::service($c, LegalHoldUseCaseInterface::class), self::json($c)),
            )
            ->set(
                ReleaseLegalHoldHandler::class,
                static fn (ContainerInterface $c): ReleaseLegalHoldHandler => new ReleaseLegalHoldHandler(self::service($c, LegalHoldUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static fn (ContainerInterface $c): LegalHoldExceptionHandler => new LegalHoldExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): LegalHoldsRouteRegistrar => new LegalHoldsRouteRegistrar(
                    self::service($c, PlaceLegalHoldHandler::class),
                    self::service($c, ReleaseLegalHoldHandler::class),
                ),
            );
    }
}
