<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\Scan\BundleScannerInterface;
use NeneServe\Support\ServiceProviderHelpers;
use Psr\Container\ContainerInterface;

final readonly class CreativesServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.creatives';

    public const string REVIEW_ROUTE_REGISTRAR = 'nene-serve.route_registrar.creative_review';

    public const string EXCEPTION_HANDLER_NOT_FOUND = 'nene-serve.exception_handler.creative_not_found';

    public const string EXCEPTION_HANDLER_TRANSITION = 'nene-serve.exception_handler.invalid_review_transition';

    public const string EXCEPTION_HANDLER_SELF_APPROVAL = 'nene-serve.exception_handler.self_approval';

    public const string EXCEPTION_HANDLER_SCAN = 'nene-serve.exception_handler.creative_scan';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                CreativeRepositoryInterface::class,
                static fn (ContainerInterface $c): CreativeRepositoryInterface => new PdoCreativeRepository(self::query($c)),
            )
            ->set(
                ListCreativesUseCaseInterface::class,
                static fn (ContainerInterface $c): ListCreativesUseCaseInterface => new ListCreativesUseCase(self::service($c, CreativeRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                GetCreativeByIdUseCaseInterface::class,
                static fn (ContainerInterface $c): GetCreativeByIdUseCaseInterface => new GetCreativeByIdUseCase(self::service($c, CreativeRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                ReviewQueueUseCaseInterface::class,
                static fn (ContainerInterface $c): ReviewQueueUseCaseInterface => new ReviewQueueUseCase(self::service($c, CreativeRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                ListCreativesHandler::class,
                static fn (ContainerInterface $c): ListCreativesHandler => new ListCreativesHandler(self::service($c, ListCreativesUseCaseInterface::class), self::json($c)),
            )
            ->set(
                GetCreativeHandler::class,
                static fn (ContainerInterface $c): GetCreativeHandler => new GetCreativeHandler(self::service($c, GetCreativeByIdUseCaseInterface::class), self::json($c)),
            )
            ->set(
                ReviewQueueHandler::class,
                static fn (ContainerInterface $c): ReviewQueueHandler => new ReviewQueueHandler(self::service($c, ReviewQueueUseCaseInterface::class), self::json($c)),
            )
            ->set(
                CreateCreativeUseCaseInterface::class,
                static fn (ContainerInterface $c): CreateCreativeUseCaseInterface => new CreateCreativeUseCase(
                    self::transactions($c),
                    self::service($c, BundleScannerInterface::class),
                    self::orgId($c),
                ),
            )
            ->set(
                ReviseCreativeUseCaseInterface::class,
                static fn (ContainerInterface $c): ReviseCreativeUseCaseInterface => new ReviseCreativeUseCase(self::query($c), self::transactions($c), self::orgId($c)),
            )
            ->set(
                CreateCreativeHandler::class,
                static fn (ContainerInterface $c): CreateCreativeHandler => new CreateCreativeHandler(self::service($c, CreateCreativeUseCaseInterface::class), self::json($c)),
            )
            ->set(
                ReviseCreativeHandler::class,
                static fn (ContainerInterface $c): ReviseCreativeHandler => new ReviseCreativeHandler(self::service($c, ReviseCreativeUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): CreativesRouteRegistrar => new CreativesRouteRegistrar(
                    self::service($c, ListCreativesHandler::class),
                    self::service($c, GetCreativeHandler::class),
                    self::service($c, ReviewQueueHandler::class),
                    self::service($c, CreateCreativeHandler::class),
                    self::service($c, ReviseCreativeHandler::class),
                ),
            )
            ->set(
                TransitionCreativeUseCaseInterface::class,
                static fn (ContainerInterface $c): TransitionCreativeUseCaseInterface => new TransitionCreativeUseCase(self::query($c), self::transactions($c), self::orgId($c)),
            )
            ->set(
                self::REVIEW_ROUTE_REGISTRAR,
                static fn (ContainerInterface $c): CreativeReviewRouteRegistrar => new CreativeReviewRouteRegistrar(self::service($c, TransitionCreativeUseCaseInterface::class), self::json($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_NOT_FOUND,
                static fn (ContainerInterface $c): CreativeNotFoundExceptionHandler => new CreativeNotFoundExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_TRANSITION,
                static fn (ContainerInterface $c): InvalidReviewTransitionExceptionHandler => new InvalidReviewTransitionExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_SELF_APPROVAL,
                static fn (ContainerInterface $c): SelfApprovalForbiddenExceptionHandler => new SelfApprovalForbiddenExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_SCAN,
                static fn (ContainerInterface $c): CreativeScanFailedExceptionHandler => new CreativeScanFailedExceptionHandler(self::problem($c)),
            );
    }
}
