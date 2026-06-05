<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class InvitationsServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.invitations';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.invitation_invalid';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                AcceptInvitationUseCaseInterface::class,
                static function (ContainerInterface $container): AcceptInvitationUseCaseInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    return new AcceptInvitationUseCase($query, $transactions);
                },
            )
            ->set(
                PreviewInvitationHandler::class,
                static function (ContainerInterface $c): PreviewInvitationHandler {
                    return new PreviewInvitationHandler(self::useCase($c), self::json($c), self::problem($c));
                },
            )
            ->set(
                AcceptInvitationHandler::class,
                static function (ContainerInterface $c): AcceptInvitationHandler {
                    return new AcceptInvitationHandler(self::useCase($c), self::json($c));
                },
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static fn (ContainerInterface $c): InvitationInvalidExceptionHandler => new InvitationInvalidExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): InvitationsRouteRegistrar {
                    $preview = $container->get(PreviewInvitationHandler::class);
                    $accept = $container->get(AcceptInvitationHandler::class);

                    if (!$preview instanceof PreviewInvitationHandler) {
                        throw new LogicException('Preview invitation handler service is invalid.');
                    }

                    if (!$accept instanceof AcceptInvitationHandler) {
                        throw new LogicException('Accept invitation handler service is invalid.');
                    }

                    return new InvitationsRouteRegistrar($preview, $accept);
                },
            );
    }

    private static function useCase(ContainerInterface $container): AcceptInvitationUseCaseInterface
    {
        $useCase = $container->get(AcceptInvitationUseCaseInterface::class);

        if (!$useCase instanceof AcceptInvitationUseCaseInterface) {
            throw new LogicException('Accept invitation use case service is invalid.');
        }

        return $useCase;
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
