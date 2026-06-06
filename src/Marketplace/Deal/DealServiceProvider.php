<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\DealOpportunityRepositoryInterface;
use NeneServe\Marketplace\PdoDealOpportunityRepository;
use NeneServe\Support\ServiceProviderHelpers;
use NeneServe\Upstream\Deal\DealClientInterface;
use Psr\Container\ContainerInterface;

final readonly class DealServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.deal';

    public const string EXCEPTION_HANDLER_NOT_FOUND = 'nene-serve.exception_handler.deal_campaign_not_found';

    public const string EXCEPTION_HANDLER_FAILED = 'nene-serve.exception_handler.deal_handoff_failed';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DealOpportunityRepositoryInterface::class,
                static fn (ContainerInterface $c): DealOpportunityRepositoryInterface => new PdoDealOpportunityRepository(self::query($c)),
            )
            ->set(
                HandoffCampaignToDealUseCaseInterface::class,
                static fn (ContainerInterface $c): HandoffCampaignToDealUseCaseInterface => new HandoffCampaignToDealUseCase(
                    self::service($c, CampaignRepositoryInterface::class),
                    self::service($c, AdvertiserRepositoryInterface::class),
                    self::service($c, DealOpportunityRepositoryInterface::class),
                    self::transactions($c),
                    self::service($c, DealClientInterface::class),
                    self::orgId($c),
                    self::dialect($c),
                ),
            )
            ->set(
                HandoffCampaignToDealHandler::class,
                static fn (ContainerInterface $c): HandoffCampaignToDealHandler => new HandoffCampaignToDealHandler(self::service($c, HandoffCampaignToDealUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_NOT_FOUND,
                static fn (ContainerInterface $c): CampaignNotFoundExceptionHandler => new CampaignNotFoundExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_FAILED,
                static fn (ContainerInterface $c): DealHandoffFailedExceptionHandler => new DealHandoffFailedExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): DealRouteRegistrar => new DealRouteRegistrar(self::service($c, HandoffCampaignToDealHandler::class)),
            );
    }
}
