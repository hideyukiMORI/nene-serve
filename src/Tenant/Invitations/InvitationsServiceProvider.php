<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Support\ServiceProviderHelpers;
use NeneServe\Tenant\InvitationRepositoryInterface;
use NeneServe\Tenant\PdoInvitationRepository;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class InvitationsServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.invitations';

    public const string EXCEPTION_HANDLER = 'nene-serve.exception_handler.invitation_invalid';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                InvitationRepositoryInterface::class,
                static function (ContainerInterface $container): InvitationRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoInvitationRepository($query);
                },
            )
            ->set(
                AcceptInvitationUseCaseInterface::class,
                static function (ContainerInterface $container): AcceptInvitationUseCaseInterface {
                    $invitations = $container->get(InvitationRepositoryInterface::class);
                    $users = $container->get(UserRepositoryInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);

                    if (!$invitations instanceof InvitationRepositoryInterface) {
                        throw new LogicException('Invitation repository service is invalid.');
                    }

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('User repository service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    return new AcceptInvitationUseCase($invitations, $users, $transactions);
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
}
