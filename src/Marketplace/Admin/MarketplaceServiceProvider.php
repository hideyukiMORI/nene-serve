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
use Nene2\Http\RequestScopedHolder;
use NeneServe\Http\RuntimeServiceProvider;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PdoPricingRuleRepository;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Marketplace\UseCase\GetCampaignSpendUseCase;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Serving\CreativeRepositoryInterface;
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
                ListAdvertisersUseCaseInterface::class,
                static function (ContainerInterface $c): ListAdvertisersUseCaseInterface {
                    $repo = $c->get(AdvertiserRepositoryInterface::class);

                    if (!$repo instanceof AdvertiserRepositoryInterface) {
                        throw new LogicException('Advertiser repository service is invalid.');
                    }

                    return new ListAdvertisersUseCase($repo, self::orgId($c));
                },
            )
            ->set(
                ListPricingRulesUseCaseInterface::class,
                static function (ContainerInterface $c): ListPricingRulesUseCaseInterface {
                    $repo = $c->get(PricingRuleRepositoryInterface::class);

                    if (!$repo instanceof PricingRuleRepositoryInterface) {
                        throw new LogicException('Pricing rule repository service is invalid.');
                    }

                    return new ListPricingRulesUseCase($repo, self::orgId($c));
                },
            )
            ->set(
                ListCampaignsUseCaseInterface::class,
                static function (ContainerInterface $c): ListCampaignsUseCaseInterface {
                    $repo = $c->get(CampaignRepositoryInterface::class);

                    if (!$repo instanceof CampaignRepositoryInterface) {
                        throw new LogicException('Campaign repository service is invalid.');
                    }

                    return new ListCampaignsUseCase($repo, self::orgId($c));
                },
            )
            ->set(
                ListAdvertisersHandler::class,
                static function (ContainerInterface $c): ListAdvertisersHandler {
                    $useCase = $c->get(ListAdvertisersUseCaseInterface::class);

                    if (!$useCase instanceof ListAdvertisersUseCaseInterface) {
                        throw new LogicException('List advertisers use case service is invalid.');
                    }

                    return new ListAdvertisersHandler($useCase, self::json($c));
                },
            )
            ->set(
                ListPricingRulesHandler::class,
                static function (ContainerInterface $c): ListPricingRulesHandler {
                    $useCase = $c->get(ListPricingRulesUseCaseInterface::class);

                    if (!$useCase instanceof ListPricingRulesUseCaseInterface) {
                        throw new LogicException('List pricing rules use case service is invalid.');
                    }

                    return new ListPricingRulesHandler($useCase, self::json($c));
                },
            )
            ->set(
                ListCampaignsHandler::class,
                static function (ContainerInterface $c): ListCampaignsHandler {
                    $useCase = $c->get(ListCampaignsUseCaseInterface::class);

                    if (!$useCase instanceof ListCampaignsUseCaseInterface) {
                        throw new LogicException('List campaigns use case service is invalid.');
                    }

                    return new ListCampaignsHandler($useCase, self::json($c));
                },
            )
            ->set(
                CreateAdvertiserUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateAdvertiserUseCaseInterface => new CreateAdvertiserUseCase(self::transactions($c), self::orgId($c)),
            )
            ->set(
                CreatePricingRuleUseCaseInterface::class,
                static function (ContainerInterface $c): CreatePricingRuleUseCaseInterface {
                    $rules = $c->get(PricingRuleRepositoryInterface::class);

                    if (!$rules instanceof PricingRuleRepositoryInterface) {
                        throw new LogicException('Pricing rule repository service is invalid.');
                    }

                    return new CreatePricingRuleUseCase($rules, self::transactions($c), self::orgId($c));
                },
            )
            ->set(
                CreateCampaignUseCaseInterface::class,
                static function (ContainerInterface $c): CreateCampaignUseCaseInterface {
                    $advertisers = $c->get(AdvertiserRepositoryInterface::class);
                    $rules = $c->get(PricingRuleRepositoryInterface::class);

                    if (!$advertisers instanceof AdvertiserRepositoryInterface) {
                        throw new LogicException('Advertiser repository service is invalid.');
                    }

                    if (!$rules instanceof PricingRuleRepositoryInterface) {
                        throw new LogicException('Pricing rule repository service is invalid.');
                    }

                    return new CreateCampaignUseCase($advertisers, $rules, self::transactions($c), self::orgId($c));
                },
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
                GetCampaignSpendUseCase::class,
                static function (ContainerInterface $c): GetCampaignSpendUseCase {
                    $creatives = $c->get(CreativeRepositoryInterface::class);
                    $events = $c->get(EventStoreInterface::class);
                    $pricingRules = $c->get(PricingRuleRepositoryInterface::class);

                    if (!$creatives instanceof CreativeRepositoryInterface) {
                        throw new LogicException('Creative repository service is invalid.');
                    }

                    if (!$events instanceof EventStoreInterface) {
                        throw new LogicException('Event store service is invalid.');
                    }

                    if (!$pricingRules instanceof PricingRuleRepositoryInterface) {
                        throw new LogicException('Pricing rule repository service is invalid.');
                    }

                    return new GetCampaignSpendUseCase($creatives, $events, $pricingRules);
                },
            )
            ->set(
                GetCampaignUseCaseInterface::class,
                static function (ContainerInterface $c): GetCampaignUseCaseInterface {
                    $campaigns = $c->get(CampaignRepositoryInterface::class);
                    $spend = $c->get(GetCampaignSpendUseCase::class);

                    if (!$campaigns instanceof CampaignRepositoryInterface) {
                        throw new LogicException('Campaign repository service is invalid.');
                    }

                    if (!$spend instanceof GetCampaignSpendUseCase) {
                        throw new LogicException('Get campaign spend use case service is invalid.');
                    }

                    return new GetCampaignUseCase($campaigns, $spend, self::orgId($c));
                },
            )
            ->set(
                GetCampaignHandler::class,
                static function (ContainerInterface $c): GetCampaignHandler {
                    $useCase = $c->get(GetCampaignUseCaseInterface::class);

                    if (!$useCase instanceof GetCampaignUseCaseInterface) {
                        throw new LogicException('Get campaign use case service is invalid.');
                    }

                    return new GetCampaignHandler($useCase, self::json($c));
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

                    $getCampaign = $container->get(GetCampaignHandler::class);

                    if (!$getCampaign instanceof GetCampaignHandler) {
                        throw new LogicException('Get campaign handler service is invalid.');
                    }

                    return new MarketplaceRouteRegistrar(
                        $advertisers,
                        $pricingRules,
                        $campaigns,
                        $createAdvertiser,
                        $createPricingRule,
                        $createCampaign,
                        $getCampaign,
                    );
                },
            );
    }

    /** @return RequestScopedHolder<string> */
    private static function orgId(ContainerInterface $container): RequestScopedHolder
    {
        $orgId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

        if (!$orgId instanceof RequestScopedHolder) {
            throw new LogicException('Organization id holder service is invalid.');
        }

        return $orgId;
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
