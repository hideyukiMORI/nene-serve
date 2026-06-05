<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\Frequency\FrequencyCapStoreInterface;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Serving\Token\TokenStoreInterface;
use NeneServe\Storage\StorageInterface;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** Wires the untrusted public serve surface `/public/*`. */
final readonly class PublicServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.public';

    private const int DEFAULT_CLICK_TOKEN_TTL = 900;

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ServeCreativeUseCaseInterface::class,
                static fn (ContainerInterface $c): ServeCreativeUseCaseInterface => new ServeCreativeUseCase(
                    self::service($c, PlacementRepositoryInterface::class),
                    self::service($c, CreativeRepositoryInterface::class),
                    self::service($c, CampaignRepositoryInterface::class),
                    self::service($c, TokenStoreInterface::class),
                    self::service($c, FrequencyCapStoreInterface::class),
                    self::service($c, EventStoreInterface::class),
                    self::service($c, GetCampaignSpendUseCase::class),
                    self::clickTokenTtl(),
                ),
            )
            ->set(
                RecordImpressionUseCaseInterface::class,
                static fn (ContainerInterface $c): RecordImpressionUseCaseInterface => new RecordImpressionUseCase(
                    self::query($c),
                    self::service($c, TokenStoreInterface::class),
                    self::service($c, EventStoreInterface::class),
                    self::service($c, FrequencyCapStoreInterface::class),
                ),
            )
            ->set(
                RecordClickUseCaseInterface::class,
                static fn (ContainerInterface $c): RecordClickUseCaseInterface => new RecordClickUseCase(
                    self::query($c),
                    self::service($c, TokenStoreInterface::class),
                    self::service($c, EventStoreInterface::class),
                ),
            )
            ->set(
                RecordConversionUseCaseInterface::class,
                static fn (ContainerInterface $c): RecordConversionUseCaseInterface => new RecordConversionUseCase(
                    self::query($c),
                    self::service($c, EventStoreInterface::class),
                ),
            )
            ->set(
                ServeHandler::class,
                static fn (ContainerInterface $c): ServeHandler => new ServeHandler(
                    self::service($c, ServeCreativeUseCaseInterface::class),
                    self::json($c),
                    self::problem($c),
                    self::service($c, ResponseFactoryInterface::class),
                ),
            )
            ->set(
                RecordImpressionHandler::class,
                static fn (ContainerInterface $c): RecordImpressionHandler => new RecordImpressionHandler(
                    self::service($c, RecordImpressionUseCaseInterface::class),
                    self::problem($c),
                    self::service($c, ResponseFactoryInterface::class),
                ),
            )
            ->set(
                RedirectClickHandler::class,
                static fn (ContainerInterface $c): RedirectClickHandler => new RedirectClickHandler(
                    self::service($c, RecordClickUseCaseInterface::class),
                    self::problem($c),
                    self::service($c, ResponseFactoryInterface::class),
                ),
            )
            ->set(
                RecordConversionHandler::class,
                static fn (ContainerInterface $c): RecordConversionHandler => new RecordConversionHandler(
                    self::service($c, RecordConversionUseCaseInterface::class),
                    self::problem($c),
                    self::service($c, ResponseFactoryInterface::class),
                ),
            )
            ->set(
                CreativeFrameHandler::class,
                static fn (ContainerInterface $c): CreativeFrameHandler => new CreativeFrameHandler(
                    self::query($c),
                    self::service($c, TokenStoreInterface::class),
                    self::problem($c),
                    self::service($c, ResponseFactoryInterface::class),
                    self::service($c, StreamFactoryInterface::class),
                ),
            )
            ->set(
                AssetHandler::class,
                static fn (ContainerInterface $c): AssetHandler => new AssetHandler(
                    self::query($c),
                    self::service($c, StorageInterface::class),
                    self::problem($c),
                    self::service($c, ResponseFactoryInterface::class),
                    self::service($c, StreamFactoryInterface::class),
                ),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): PublicRouteRegistrar => new PublicRouteRegistrar(
                    self::service($c, ServeHandler::class),
                    self::service($c, RecordImpressionHandler::class),
                    self::service($c, RedirectClickHandler::class),
                    self::service($c, CreativeFrameHandler::class),
                    self::service($c, AssetHandler::class),
                    self::service($c, RecordConversionHandler::class),
                ),
            );
    }

    private static function clickTokenTtl(): int
    {
        $ttl = getenv('NENE_SERVE_CLICK_TOKEN_TTL');

        return is_string($ttl) && ctype_digit($ttl) ? (int) $ttl : self::DEFAULT_CLICK_TOKEN_TTL;
    }
}
