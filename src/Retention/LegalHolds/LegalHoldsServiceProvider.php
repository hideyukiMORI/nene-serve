<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Http\RuntimeServiceProvider;
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
                static function (ContainerInterface $container): LegalHoldRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoLegalHoldRepository($query);
                },
            )
            ->set(
                LegalHoldUseCaseInterface::class,
                static function (ContainerInterface $container): LegalHoldUseCaseInterface {
                    $holds = $container->get(LegalHoldRepositoryInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);
                    $organizationId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$holds instanceof LegalHoldRepositoryInterface) {
                        throw new LogicException('Legal hold repository service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new LegalHoldUseCase($holds, $transactions, $organizationId);
                },
            )
            ->set(
                PlaceLegalHoldHandler::class,
                static fn (ContainerInterface $c): PlaceLegalHoldHandler => new PlaceLegalHoldHandler(self::useCase($c), self::json($c)),
            )
            ->set(
                ReleaseLegalHoldHandler::class,
                static fn (ContainerInterface $c): ReleaseLegalHoldHandler => new ReleaseLegalHoldHandler(self::useCase($c), self::json($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static fn (ContainerInterface $c): LegalHoldExceptionHandler => new LegalHoldExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): LegalHoldsRouteRegistrar {
                    $place = $container->get(PlaceLegalHoldHandler::class);
                    $release = $container->get(ReleaseLegalHoldHandler::class);

                    if (!$place instanceof PlaceLegalHoldHandler) {
                        throw new LogicException('Place legal hold handler service is invalid.');
                    }

                    if (!$release instanceof ReleaseLegalHoldHandler) {
                        throw new LogicException('Release legal hold handler service is invalid.');
                    }

                    return new LegalHoldsRouteRegistrar($place, $release);
                },
            );
    }

    private static function useCase(ContainerInterface $container): LegalHoldUseCaseInterface
    {
        $useCase = $container->get(LegalHoldUseCaseInterface::class);

        if (!$useCase instanceof LegalHoldUseCaseInterface) {
            throw new LogicException('Legal hold use case service is invalid.');
        }

        return $useCase;
    }
}
