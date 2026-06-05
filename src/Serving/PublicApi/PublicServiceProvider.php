<?php

declare(strict_types=1);

namespace NeneServe\Serving\PublicApi;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Serving\Frequency\FrequencyCapStoreInterface;
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
                static function (ContainerInterface $c): ServeCreativeUseCaseInterface {
                    $spend = $c->get(GetCampaignSpendUseCase::class);

                    if (!$spend instanceof GetCampaignSpendUseCase) {
                        throw new LogicException('Get campaign spend use case service is invalid.');
                    }

                    return new ServeCreativeUseCase(
                        self::query($c),
                        self::tokens($c),
                        self::frequencyCaps($c),
                        self::events($c),
                        $spend,
                        self::clickTokenTtl(),
                    );
                },
            )
            ->set(
                RecordImpressionUseCaseInterface::class,
                static fn (ContainerInterface $c): RecordImpressionUseCaseInterface => new RecordImpressionUseCase(
                    self::query($c),
                    self::tokens($c),
                    self::events($c),
                    self::frequencyCaps($c),
                ),
            )
            ->set(
                RecordClickUseCaseInterface::class,
                static fn (ContainerInterface $c): RecordClickUseCaseInterface => new RecordClickUseCase(
                    self::query($c),
                    self::tokens($c),
                    self::events($c),
                ),
            )
            ->set(
                RecordConversionUseCaseInterface::class,
                static fn (ContainerInterface $c): RecordConversionUseCaseInterface => new RecordConversionUseCase(
                    self::query($c),
                    self::events($c),
                ),
            )
            ->set(
                ServeHandler::class,
                static function (ContainerInterface $c): ServeHandler {
                    $useCase = $c->get(ServeCreativeUseCaseInterface::class);

                    if (!$useCase instanceof ServeCreativeUseCaseInterface) {
                        throw new LogicException('Serve creative use case service is invalid.');
                    }

                    return new ServeHandler($useCase, self::json($c), self::problem($c), self::responseFactory($c));
                },
            )
            ->set(
                RecordImpressionHandler::class,
                static function (ContainerInterface $c): RecordImpressionHandler {
                    $useCase = $c->get(RecordImpressionUseCaseInterface::class);

                    if (!$useCase instanceof RecordImpressionUseCaseInterface) {
                        throw new LogicException('Record impression use case service is invalid.');
                    }

                    return new RecordImpressionHandler($useCase, self::problem($c), self::responseFactory($c));
                },
            )
            ->set(
                RedirectClickHandler::class,
                static function (ContainerInterface $c): RedirectClickHandler {
                    $useCase = $c->get(RecordClickUseCaseInterface::class);

                    if (!$useCase instanceof RecordClickUseCaseInterface) {
                        throw new LogicException('Record click use case service is invalid.');
                    }

                    return new RedirectClickHandler($useCase, self::problem($c), self::responseFactory($c));
                },
            )
            ->set(
                RecordConversionHandler::class,
                static function (ContainerInterface $c): RecordConversionHandler {
                    $useCase = $c->get(RecordConversionUseCaseInterface::class);

                    if (!$useCase instanceof RecordConversionUseCaseInterface) {
                        throw new LogicException('Record conversion use case service is invalid.');
                    }

                    return new RecordConversionHandler($useCase, self::problem($c), self::responseFactory($c));
                },
            )
            ->set(
                CreativeFrameHandler::class,
                static fn (ContainerInterface $c): CreativeFrameHandler => new CreativeFrameHandler(
                    self::query($c),
                    self::tokens($c),
                    self::problem($c),
                    self::responseFactory($c),
                    self::streamFactory($c),
                ),
            )
            ->set(
                AssetHandler::class,
                static function (ContainerInterface $c): AssetHandler {
                    $storage = $c->get(StorageInterface::class);

                    if (!$storage instanceof StorageInterface) {
                        throw new LogicException('Storage service is invalid.');
                    }

                    return new AssetHandler(self::query($c), $storage, self::problem($c), self::responseFactory($c), self::streamFactory($c));
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $c): PublicRouteRegistrar {
                    $serve = $c->get(ServeHandler::class);
                    $impression = $c->get(RecordImpressionHandler::class);
                    $click = $c->get(RedirectClickHandler::class);
                    $frame = $c->get(CreativeFrameHandler::class);
                    $asset = $c->get(AssetHandler::class);
                    $conversion = $c->get(RecordConversionHandler::class);

                    if (!$serve instanceof ServeHandler) {
                        throw new LogicException('Serve handler service is invalid.');
                    }

                    if (!$impression instanceof RecordImpressionHandler) {
                        throw new LogicException('Record impression handler service is invalid.');
                    }

                    if (!$click instanceof RedirectClickHandler) {
                        throw new LogicException('Redirect click handler service is invalid.');
                    }

                    if (!$frame instanceof CreativeFrameHandler) {
                        throw new LogicException('Creative frame handler service is invalid.');
                    }

                    if (!$asset instanceof AssetHandler) {
                        throw new LogicException('Asset handler service is invalid.');
                    }

                    if (!$conversion instanceof RecordConversionHandler) {
                        throw new LogicException('Record conversion handler service is invalid.');
                    }

                    return new PublicRouteRegistrar($serve, $impression, $click, $frame, $asset, $conversion);
                },
            );
    }

    private static function clickTokenTtl(): int
    {
        $ttl = getenv('NENE_SERVE_CLICK_TOKEN_TTL');

        return is_string($ttl) && ctype_digit($ttl) ? (int) $ttl : self::DEFAULT_CLICK_TOKEN_TTL;
    }

    private static function tokens(ContainerInterface $container): TokenStoreInterface
    {
        $tokens = $container->get(TokenStoreInterface::class);

        if (!$tokens instanceof TokenStoreInterface) {
            throw new LogicException('Token store service is invalid.');
        }

        return $tokens;
    }

    private static function frequencyCaps(ContainerInterface $container): FrequencyCapStoreInterface
    {
        $frequencyCaps = $container->get(FrequencyCapStoreInterface::class);

        if (!$frequencyCaps instanceof FrequencyCapStoreInterface) {
            throw new LogicException('Frequency cap store service is invalid.');
        }

        return $frequencyCaps;
    }

    private static function events(ContainerInterface $container): EventStoreInterface
    {
        $events = $container->get(EventStoreInterface::class);

        if (!$events instanceof EventStoreInterface) {
            throw new LogicException('Event store service is invalid.');
        }

        return $events;
    }

    private static function responseFactory(ContainerInterface $container): ResponseFactoryInterface
    {
        $factory = $container->get(ResponseFactoryInterface::class);

        if (!$factory instanceof ResponseFactoryInterface) {
            throw new LogicException('Response factory service is invalid.');
        }

        return $factory;
    }

    private static function streamFactory(ContainerInterface $container): StreamFactoryInterface
    {
        $factory = $container->get(StreamFactoryInterface::class);

        if (!$factory instanceof StreamFactoryInterface) {
            throw new LogicException('Stream factory service is invalid.');
        }

        return $factory;
    }
}
