<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Deal;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Upstream\Deal\DealClientInterface;
use Psr\Container\ContainerInterface;

final readonly class DealServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.deal';

    public const string EXCEPTION_HANDLER_NOT_FOUND = 'nene-serve.exception_handler.deal_campaign_not_found';

    public const string EXCEPTION_HANDLER_FAILED = 'nene-serve.exception_handler.deal_handoff_failed';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                HandoffCampaignToDealUseCaseInterface::class,
                static function (ContainerInterface $c): HandoffCampaignToDealUseCaseInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    $transactions = $c->get(DatabaseTransactionManagerInterface::class);
                    $deal = $c->get(DealClientInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    if (!$deal instanceof DealClientInterface) {
                        throw new LogicException('Deal client service is invalid.');
                    }

                    return new HandoffCampaignToDealUseCase($query, $transactions, $deal);
                },
            )
            ->set(
                HandoffCampaignToDealHandler::class,
                static function (ContainerInterface $c): HandoffCampaignToDealHandler {
                    $useCase = $c->get(HandoffCampaignToDealUseCaseInterface::class);

                    if (!$useCase instanceof HandoffCampaignToDealUseCaseInterface) {
                        throw new LogicException('Handoff campaign to deal use case service is invalid.');
                    }

                    return new HandoffCampaignToDealHandler($useCase, self::json($c), self::problem($c));
                },
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
                static function (ContainerInterface $container): DealRouteRegistrar {
                    $handler = $container->get(HandoffCampaignToDealHandler::class);

                    if (!$handler instanceof HandoffCampaignToDealHandler) {
                        throw new LogicException('Handoff campaign to deal handler service is invalid.');
                    }

                    return new DealRouteRegistrar($handler);
                },
            );
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
