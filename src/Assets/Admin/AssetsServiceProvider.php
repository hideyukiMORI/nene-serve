<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Http\RuntimeServiceProvider;
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
                static function (ContainerInterface $c): UploadAssetUseCaseInterface {
                    $storage = $c->get(StorageInterface::class);
                    $organizationId = $c->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$storage instanceof StorageInterface) {
                        throw new LogicException('Storage service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new UploadAssetUseCase(self::transactions($c), $storage, $organizationId);
                },
            )
            ->set(
                GetRecordsAssetUseCaseInterface::class,
                static function (ContainerInterface $c): GetRecordsAssetUseCaseInterface {
                    $records = $c->get(RecordsClientInterface::class);

                    if (!$records instanceof RecordsClientInterface) {
                        throw new LogicException('Records client service is invalid.');
                    }

                    return new GetRecordsAssetUseCase($records);
                },
            )
            ->set(
                UploadAssetHandler::class,
                static function (ContainerInterface $c): UploadAssetHandler {
                    $useCase = $c->get(UploadAssetUseCaseInterface::class);

                    if (!$useCase instanceof UploadAssetUseCaseInterface) {
                        throw new LogicException('Upload asset use case service is invalid.');
                    }

                    return new UploadAssetHandler($useCase, self::json($c));
                },
            )
            ->set(
                GetRecordsAssetHandler::class,
                static function (ContainerInterface $c): GetRecordsAssetHandler {
                    $useCase = $c->get(GetRecordsAssetUseCaseInterface::class);

                    if (!$useCase instanceof GetRecordsAssetUseCaseInterface) {
                        throw new LogicException('Get records asset use case service is invalid.');
                    }

                    return new GetRecordsAssetHandler($useCase, self::json($c), self::problem($c));
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
}
