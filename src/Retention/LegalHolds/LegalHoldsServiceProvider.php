<?php

declare(strict_types=1);

namespace NeneServe\Retention\LegalHolds;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class LegalHoldsServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.legal_holds';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.legal_hold';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                LegalHoldUseCaseInterface::class,
                static function (ContainerInterface $container): LegalHoldUseCaseInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    return new LegalHoldUseCase($query, $transactions);
                },
            )
            ->set(
                PlaceLegalHoldHandler::class,
                static fn (ContainerInterface $c): PlaceLegalHoldHandler => new PlaceLegalHoldHandler(self::useCase($c), self::json($c), self::problem($c)),
            )
            ->set(
                ReleaseLegalHoldHandler::class,
                static fn (ContainerInterface $c): ReleaseLegalHoldHandler => new ReleaseLegalHoldHandler(self::useCase($c), self::json($c), self::problem($c)),
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
