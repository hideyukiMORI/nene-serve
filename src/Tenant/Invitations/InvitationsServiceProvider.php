<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

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
                static fn (ContainerInterface $c): InvitationRepositoryInterface => new PdoInvitationRepository(self::query($c)),
            )
            ->set(
                AcceptInvitationUseCaseInterface::class,
                static fn (ContainerInterface $c): AcceptInvitationUseCaseInterface => new AcceptInvitationUseCase(
                    self::service($c, InvitationRepositoryInterface::class),
                    self::service($c, UserRepositoryInterface::class),
                    self::transactions($c),
                    self::dialect($c),
                ),
            )
            ->set(
                PreviewInvitationHandler::class,
                static fn (ContainerInterface $c): PreviewInvitationHandler => new PreviewInvitationHandler(self::service($c, AcceptInvitationUseCaseInterface::class), self::json($c), self::problem($c)),
            )
            ->set(
                AcceptInvitationHandler::class,
                static fn (ContainerInterface $c): AcceptInvitationHandler => new AcceptInvitationHandler(self::service($c, AcceptInvitationUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER,
                static fn (ContainerInterface $c): InvitationInvalidExceptionHandler => new InvitationInvalidExceptionHandler(self::problem($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): InvitationsRouteRegistrar => new InvitationsRouteRegistrar(
                    self::service($c, PreviewInvitationHandler::class),
                    self::service($c, AcceptInvitationHandler::class),
                ),
            );
    }
}
