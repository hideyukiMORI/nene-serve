<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PdoPricingRuleRepository;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;

final readonly class MarketplaceServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

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
                ListAdvertisersUseCaseInterface::class,
                static fn (ContainerInterface $c): ListAdvertisersUseCaseInterface => new ListAdvertisersUseCase(self::service($c, AdvertiserRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                ListPricingRulesUseCaseInterface::class,
                static fn (ContainerInterface $c): ListPricingRulesUseCaseInterface => new ListPricingRulesUseCase(self::service($c, PricingRuleRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                ListCampaignsUseCaseInterface::class,
                static fn (ContainerInterface $c): ListCampaignsUseCaseInterface => new ListCampaignsUseCase(self::service($c, CampaignRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                ListAdvertisersHandler::class,
                static fn (ContainerInterface $c): ListAdvertisersHandler => new ListAdvertisersHandler(self::service($c, ListAdvertisersUseCaseInterface::class), self::json($c)),
            )
            ->set(
                ListPricingRulesHandler::class,
                static fn (ContainerInterface $c): ListPricingRulesHandler => new ListPricingRulesHandler(self::service($c, ListPricingRulesUseCaseInterface::class), self::json($c)),
            )
            ->set(
                ListCampaignsHandler::class,
                static fn (ContainerInterface $c): ListCampaignsHandler => new ListCampaignsHandler(self::service($c, ListCampaignsUseCaseInterface::class), self::json($c)),
            )
            ->set(
                CreateAdvertiserUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateAdvertiserUseCaseInterface => new CreateAdvertiserUseCase(self::transactions($c), self::orgId($c), self::dialect($c)),
            )
            ->set(
                CreatePricingRuleUseCaseInterface::class,
                static fn (ContainerInterface $c): CreatePricingRuleUseCaseInterface => new CreatePricingRuleUseCase(
                    self::service($c, PricingRuleRepositoryInterface::class),
                    self::transactions($c),
                    self::orgId($c),
                    self::clock($c),
                ),
            )
            ->set(
                CreateCampaignUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateCampaignUseCaseInterface => new CreateCampaignUseCase(
                    self::service($c, AdvertiserRepositoryInterface::class),
                    self::service($c, PricingRuleRepositoryInterface::class),
                    self::transactions($c),
                    self::orgId($c),
                    self::dialect($c),
                ),
            )
            ->set(
                CreateAdvertiserHandler::class,
                static fn (ContainerInterface $c): CreateAdvertiserHandler => new CreateAdvertiserHandler(self::service($c, CreateAdvertiserUseCaseInterface::class), self::json($c)),
            )
            ->set(
                CreatePricingRuleHandler::class,
                static fn (ContainerInterface $c): CreatePricingRuleHandler => new CreatePricingRuleHandler(self::service($c, CreatePricingRuleUseCaseInterface::class), self::json($c)),
            )
            ->set(
                CreateCampaignHandler::class,
                static fn (ContainerInterface $c): CreateCampaignHandler => new CreateCampaignHandler(self::service($c, CreateCampaignUseCaseInterface::class), self::json($c)),
            )
            ->set(
                GetCampaignSpendUseCase::class,
                static fn (ContainerInterface $c): GetCampaignSpendUseCase => new GetCampaignSpendUseCase(
                    self::service($c, CreativeRepositoryInterface::class),
                    self::service($c, EventStoreInterface::class),
                    self::service($c, PricingRuleRepositoryInterface::class),
                ),
            )
            ->set(
                GetCampaignUseCaseInterface::class,
                static fn (ContainerInterface $c): GetCampaignUseCaseInterface => new GetCampaignUseCase(
                    self::service($c, CampaignRepositoryInterface::class),
                    self::service($c, GetCampaignSpendUseCase::class),
                    self::orgId($c),
                ),
            )
            ->set(
                GetCampaignHandler::class,
                static fn (ContainerInterface $c): GetCampaignHandler => new GetCampaignHandler(self::service($c, GetCampaignUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static fn (ContainerInterface $c): MarketplaceValidationExceptionHandler => new MarketplaceValidationExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): MarketplaceRouteRegistrar => new MarketplaceRouteRegistrar(
                    self::service($c, ListAdvertisersHandler::class),
                    self::service($c, ListPricingRulesHandler::class),
                    self::service($c, ListCampaignsHandler::class),
                    self::service($c, CreateAdvertiserHandler::class),
                    self::service($c, CreatePricingRuleHandler::class),
                    self::service($c, CreateCampaignHandler::class),
                    self::service($c, GetCampaignHandler::class),
                ),
            );
    }
}
