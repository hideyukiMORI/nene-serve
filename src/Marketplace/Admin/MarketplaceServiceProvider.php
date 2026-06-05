<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PdoPricingRuleRepository;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class MarketplaceServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.marketplace';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.marketplace_validation';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                AdvertiserRepositoryInterface::class,
                static fn (ContainerInterface $c): AdvertiserRepositoryInterface => new PdoAdvertiserRepository(self::query($c)),
            )
            ->set(
                PricingRuleRepositoryInterface::class,
                static fn (ContainerInterface $c): PricingRuleRepositoryInterface => new PdoPricingRuleRepository(self::query($c)),
            )
            ->set(
                CampaignRepositoryInterface::class,
                static fn (ContainerInterface $c): CampaignRepositoryInterface => new PdoCampaignRepository(self::query($c)),
            )
            ->set(
                ListAdvertisersHandler::class,
                static function (ContainerInterface $c): ListAdvertisersHandler {
                    $repo = $c->get(AdvertiserRepositoryInterface::class);

                    if (!$repo instanceof AdvertiserRepositoryInterface) {
                        throw new LogicException('Advertiser repository service is invalid.');
                    }

                    return new ListAdvertisersHandler($repo, self::json($c), self::problem($c));
                },
            )
            ->set(
                ListPricingRulesHandler::class,
                static function (ContainerInterface $c): ListPricingRulesHandler {
                    $repo = $c->get(PricingRuleRepositoryInterface::class);

                    if (!$repo instanceof PricingRuleRepositoryInterface) {
                        throw new LogicException('Pricing rule repository service is invalid.');
                    }

                    return new ListPricingRulesHandler($repo, self::json($c), self::problem($c));
                },
            )
            ->set(
                ListCampaignsHandler::class,
                static function (ContainerInterface $c): ListCampaignsHandler {
                    $repo = $c->get(CampaignRepositoryInterface::class);

                    if (!$repo instanceof CampaignRepositoryInterface) {
                        throw new LogicException('Campaign repository service is invalid.');
                    }

                    return new ListCampaignsHandler($repo, self::json($c), self::problem($c));
                },
            )
            ->set(
                CreateAdvertiserUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateAdvertiserUseCaseInterface => new CreateAdvertiserUseCase(self::transactions($c)),
            )
            ->set(
                CreatePricingRuleUseCaseInterface::class,
                static fn (ContainerInterface $c): CreatePricingRuleUseCaseInterface => new CreatePricingRuleUseCase(self::query($c), self::transactions($c)),
            )
            ->set(
                CreateCampaignUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateCampaignUseCaseInterface => new CreateCampaignUseCase(self::query($c), self::transactions($c)),
            )
            ->set(
                CreateAdvertiserHandler::class,
                static function (ContainerInterface $c): CreateAdvertiserHandler {
                    $useCase = $c->get(CreateAdvertiserUseCaseInterface::class);

                    if (!$useCase instanceof CreateAdvertiserUseCaseInterface) {
                        throw new LogicException('Create advertiser use case service is invalid.');
                    }

                    return new CreateAdvertiserHandler($useCase, self::json($c), self::problem($c));
                },
            )
            ->set(
                CreatePricingRuleHandler::class,
                static function (ContainerInterface $c): CreatePricingRuleHandler {
                    $useCase = $c->get(CreatePricingRuleUseCaseInterface::class);

                    if (!$useCase instanceof CreatePricingRuleUseCaseInterface) {
                        throw new LogicException('Create pricing rule use case service is invalid.');
                    }

                    return new CreatePricingRuleHandler($useCase, self::json($c), self::problem($c));
                },
            )
            ->set(
                CreateCampaignHandler::class,
                static function (ContainerInterface $c): CreateCampaignHandler {
                    $useCase = $c->get(CreateCampaignUseCaseInterface::class);

                    if (!$useCase instanceof CreateCampaignUseCaseInterface) {
                        throw new LogicException('Create campaign use case service is invalid.');
                    }

                    return new CreateCampaignHandler($useCase, self::json($c), self::problem($c));
                },
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static fn (ContainerInterface $c): MarketplaceValidationExceptionHandler => new MarketplaceValidationExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): MarketplaceRouteRegistrar {
                    $advertisers = $container->get(ListAdvertisersHandler::class);
                    $pricingRules = $container->get(ListPricingRulesHandler::class);
                    $campaigns = $container->get(ListCampaignsHandler::class);
                    $createAdvertiser = $container->get(CreateAdvertiserHandler::class);
                    $createPricingRule = $container->get(CreatePricingRuleHandler::class);
                    $createCampaign = $container->get(CreateCampaignHandler::class);

                    if (!$advertisers instanceof ListAdvertisersHandler) {
                        throw new LogicException('List advertisers handler service is invalid.');
                    }

                    if (!$pricingRules instanceof ListPricingRulesHandler) {
                        throw new LogicException('List pricing rules handler service is invalid.');
                    }

                    if (!$campaigns instanceof ListCampaignsHandler) {
                        throw new LogicException('List campaigns handler service is invalid.');
                    }

                    if (!$createAdvertiser instanceof CreateAdvertiserHandler) {
                        throw new LogicException('Create advertiser handler service is invalid.');
                    }

                    if (!$createPricingRule instanceof CreatePricingRuleHandler) {
                        throw new LogicException('Create pricing rule handler service is invalid.');
                    }

                    if (!$createCampaign instanceof CreateCampaignHandler) {
                        throw new LogicException('Create campaign handler service is invalid.');
                    }

                    return new MarketplaceRouteRegistrar(
                        $advertisers,
                        $pricingRules,
                        $campaigns,
                        $createAdvertiser,
                        $createPricingRule,
                        $createCampaign,
                    );
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

    private static function query(ContainerInterface $container): DatabaseQueryExecutorInterface
    {
        $query = $container->get(DatabaseQueryExecutorInterface::class);

        if (!$query instanceof DatabaseQueryExecutorInterface) {
            throw new LogicException('Database query executor service is invalid.');
        }

        return $query;
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
