<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\PdoCreativeRepository;
use Psr\Container\ContainerInterface;

final readonly class CreativesServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.creatives';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                CreativeRepositoryInterface::class,
                static function (ContainerInterface $container): CreativeRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoCreativeRepository($query);
                },
            )
            ->set(
                ListCreativesHandler::class,
                static fn (ContainerInterface $c): ListCreativesHandler => new ListCreativesHandler(
                    self::creatives($c),
                    self::json($c),
                    self::problem($c),
                ),
            )
            ->set(
                GetCreativeHandler::class,
                static fn (ContainerInterface $c): GetCreativeHandler => new GetCreativeHandler(
                    self::creatives($c),
                    self::json($c),
                    self::problem($c),
                ),
            )
            ->set(
                ReviewQueueHandler::class,
                static fn (ContainerInterface $c): ReviewQueueHandler => new ReviewQueueHandler(
                    self::creatives($c),
                    self::json($c),
                    self::problem($c),
                ),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): CreativesRouteRegistrar {
                    $list = $container->get(ListCreativesHandler::class);
                    $get = $container->get(GetCreativeHandler::class);
                    $queue = $container->get(ReviewQueueHandler::class);

                    if (!$list instanceof ListCreativesHandler) {
                        throw new LogicException('List creatives handler service is invalid.');
                    }

                    if (!$get instanceof GetCreativeHandler) {
                        throw new LogicException('Get creative handler service is invalid.');
                    }

                    if (!$queue instanceof ReviewQueueHandler) {
                        throw new LogicException('Review queue handler service is invalid.');
                    }

                    return new CreativesRouteRegistrar($list, $get, $queue);
                },
            );
    }

    private static function creatives(ContainerInterface $container): CreativeRepositoryInterface
    {
        $creatives = $container->get(CreativeRepositoryInterface::class);

        if (!$creatives instanceof CreativeRepositoryInterface) {
            throw new LogicException('Creative repository service is invalid.');
        }

        return $creatives;
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
