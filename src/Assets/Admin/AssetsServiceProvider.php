<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use LogicException;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Storage\StorageInterface;
use NeneServe\Upstream\Records\RecordsClientInterface;
use Psr\Container\ContainerInterface;

final readonly class AssetsServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.assets';

    public const string EXCEPTION_HANDLER_VALIDATION = 'nene-serve.exception_handler.asset_validation';

    public const string EXCEPTION_HANDLER_RECORDS = 'nene-serve.exception_handler.records_unavailable';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                UploadAssetUseCaseInterface::class,
                static function (ContainerInterface $c): UploadAssetUseCaseInterface {
                    $storage = $c->get(StorageInterface::class);

                    if (!$storage instanceof StorageInterface) {
                        throw new LogicException('Storage service is invalid.');
                    }

                    return new UploadAssetUseCase(self::transactions($c), $storage);
                },
            )
            ->set(
                UploadAssetHandler::class,
                static function (ContainerInterface $c): UploadAssetHandler {
                    $useCase = $c->get(UploadAssetUseCaseInterface::class);

                    if (!$useCase instanceof UploadAssetUseCaseInterface) {
                        throw new LogicException('Upload asset use case service is invalid.');
                    }

                    return new UploadAssetHandler($useCase, self::json($c), self::problem($c));
                },
            )
            ->set(
                GetRecordsAssetHandler::class,
                static function (ContainerInterface $c): GetRecordsAssetHandler {
                    $records = $c->get(RecordsClientInterface::class);

                    if (!$records instanceof RecordsClientInterface) {
                        throw new LogicException('Records client service is invalid.');
                    }

                    return new GetRecordsAssetHandler($records, self::json($c), self::problem($c));
                },
            )
            ->set(
                self::EXCEPTION_HANDLER_VALIDATION,
                static fn (ContainerInterface $c): AssetValidationExceptionHandler => new AssetValidationExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_RECORDS,
                static fn (ContainerInterface $c): RecordsUnavailableExceptionHandler => new RecordsUnavailableExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): AssetsRouteRegistrar {
                    $upload = $container->get(UploadAssetHandler::class);
                    $records = $container->get(GetRecordsAssetHandler::class);

                    if (!$upload instanceof UploadAssetHandler) {
                        throw new LogicException('Upload asset handler service is invalid.');
                    }

                    if (!$records instanceof GetRecordsAssetHandler) {
                        throw new LogicException('Get records asset handler service is invalid.');
                    }

                    return new AssetsRouteRegistrar($upload, $records);
                },
            );
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
}
