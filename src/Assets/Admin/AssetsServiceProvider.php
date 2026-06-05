<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Storage\StorageInterface;
use NeneServe\Support\ServiceProviderHelpers;
use NeneServe\Upstream\Records\RecordsClientInterface;
use Psr\Container\ContainerInterface;

final readonly class AssetsServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.assets';

    public const string EXCEPTION_HANDLER_VALIDATION = 'nene-serve.exception_handler.asset_validation';

    public const string EXCEPTION_HANDLER_RECORDS = 'nene-serve.exception_handler.records_unavailable';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                UploadAssetUseCaseInterface::class,
                static fn (ContainerInterface $c): UploadAssetUseCaseInterface => new UploadAssetUseCase(
                    self::transactions($c),
                    self::service($c, StorageInterface::class),
                    self::orgId($c),
                ),
            )
            ->set(
                GetRecordsAssetUseCaseInterface::class,
                static fn (ContainerInterface $c): GetRecordsAssetUseCaseInterface => new GetRecordsAssetUseCase(self::service($c, RecordsClientInterface::class)),
            )
            ->set(
                UploadAssetHandler::class,
                static fn (ContainerInterface $c): UploadAssetHandler => new UploadAssetHandler(self::service($c, UploadAssetUseCaseInterface::class), self::json($c)),
            )
            ->set(
                GetRecordsAssetHandler::class,
                static fn (ContainerInterface $c): GetRecordsAssetHandler => new GetRecordsAssetHandler(self::service($c, GetRecordsAssetUseCaseInterface::class), self::json($c), self::problem($c)),
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
                static fn (ContainerInterface $c): AssetsRouteRegistrar => new AssetsRouteRegistrar(
                    self::service($c, UploadAssetHandler::class),
                    self::service($c, GetRecordsAssetHandler::class),
                ),
            );
    }
}
