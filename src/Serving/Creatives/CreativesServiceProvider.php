<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
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
                static function (ContainerInterface $container): CreativeRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoCreativeRepository($query);
                },
            )
            ->set(
                ListCreativesUseCaseInterface::class,
                static fn (ContainerInterface $c): ListCreativesUseCaseInterface => new ListCreativesUseCase(self::creatives($c), self::orgId($c)),
            )
            ->set(
                GetCreativeByIdUseCaseInterface::class,
                static fn (ContainerInterface $c): GetCreativeByIdUseCaseInterface => new GetCreativeByIdUseCase(self::creatives($c), self::orgId($c)),
            )
            ->set(
                ReviewQueueUseCaseInterface::class,
                static fn (ContainerInterface $c): ReviewQueueUseCaseInterface => new ReviewQueueUseCase(self::creatives($c), self::orgId($c)),
            )
            ->set(
                ListCreativesHandler::class,
                static function (ContainerInterface $c): ListCreativesHandler {
                    $useCase = $c->get(ListCreativesUseCaseInterface::class);

                    if (!$useCase instanceof ListCreativesUseCaseInterface) {
                        throw new LogicException('List creatives use case service is invalid.');
                    }

                    return new ListCreativesHandler($useCase, self::json($c));
                },
            )
            ->set(
                GetCreativeHandler::class,
                static function (ContainerInterface $c): GetCreativeHandler {
                    $useCase = $c->get(GetCreativeByIdUseCaseInterface::class);

                    if (!$useCase instanceof GetCreativeByIdUseCaseInterface) {
                        throw new LogicException('Get creative use case service is invalid.');
                    }

                    return new GetCreativeHandler($useCase, self::json($c));
                },
            )
            ->set(
                ReviewQueueHandler::class,
                static function (ContainerInterface $c): ReviewQueueHandler {
                    $useCase = $c->get(ReviewQueueUseCaseInterface::class);

                    if (!$useCase instanceof ReviewQueueUseCaseInterface) {
                        throw new LogicException('Review queue use case service is invalid.');
                    }

                    return new ReviewQueueHandler($useCase, self::json($c));
                },
            )
            ->set(
                CreateCreativeUseCaseInterface::class,
                static function (ContainerInterface $container): CreateCreativeUseCaseInterface {
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);
                    $scanner = $container->get(BundleScannerInterface::class);

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    if (!$scanner instanceof BundleScannerInterface) {
                        throw new LogicException('Bundle scanner service is invalid.');
                    }

                    return new CreateCreativeUseCase($transactions, $scanner, self::orgId($container));
                },
            )
            ->set(
                ReviseCreativeUseCaseInterface::class,
                static function (ContainerInterface $container): ReviseCreativeUseCaseInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    return new ReviseCreativeUseCase($query, $transactions, self::orgId($container));
                },
            )
            ->set(
                CreateCreativeHandler::class,
                static function (ContainerInterface $container): CreateCreativeHandler {
                    $useCase = $container->get(CreateCreativeUseCaseInterface::class);

                    if (!$useCase instanceof CreateCreativeUseCaseInterface) {
                        throw new LogicException('Create creative use case service is invalid.');
                    }

                    return new CreateCreativeHandler($useCase, self::json($container));
                },
            )
            ->set(
                ReviseCreativeHandler::class,
                static function (ContainerInterface $container): ReviseCreativeHandler {
                    $useCase = $container->get(ReviseCreativeUseCaseInterface::class);

                    if (!$useCase instanceof ReviseCreativeUseCaseInterface) {
                        throw new LogicException('Revise creative use case service is invalid.');
                    }

                    return new ReviseCreativeHandler($useCase, self::json($container));
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): CreativesRouteRegistrar {
                    $list = $container->get(ListCreativesHandler::class);
                    $get = $container->get(GetCreativeHandler::class);
                    $queue = $container->get(ReviewQueueHandler::class);
                    $create = $container->get(CreateCreativeHandler::class);
                    $revise = $container->get(ReviseCreativeHandler::class);

                    if (!$list instanceof ListCreativesHandler) {
                        throw new LogicException('List creatives handler service is invalid.');
                    }

                    if (!$get instanceof GetCreativeHandler) {
                        throw new LogicException('Get creative handler service is invalid.');
                    }

                    if (!$queue instanceof ReviewQueueHandler) {
                        throw new LogicException('Review queue handler service is invalid.');
                    }

                    if (!$create instanceof CreateCreativeHandler) {
                        throw new LogicException('Create creative handler service is invalid.');
                    }

                    if (!$revise instanceof ReviseCreativeHandler) {
                        throw new LogicException('Revise creative handler service is invalid.');
                    }

                    return new CreativesRouteRegistrar($list, $get, $queue, $create, $revise);
                },
            )
            ->set(
                TransitionCreativeUseCaseInterface::class,
                static function (ContainerInterface $container): TransitionCreativeUseCaseInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    $transactions = $container->get(DatabaseTransactionManagerInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$transactions instanceof DatabaseTransactionManagerInterface) {
                        throw new LogicException('Database transaction manager service is invalid.');
                    }

                    return new TransitionCreativeUseCase($query, $transactions, self::orgId($container));
                },
            )
            ->set(
                self::REVIEW_ROUTE_REGISTRAR,
                static function (ContainerInterface $container): CreativeReviewRouteRegistrar {
                    $useCase = $container->get(TransitionCreativeUseCaseInterface::class);

                    if (!$useCase instanceof TransitionCreativeUseCaseInterface) {
                        throw new LogicException('Transition creative use case service is invalid.');
                    }

                    return new CreativeReviewRouteRegistrar($useCase, self::json($container));
                },
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

    private static function creatives(ContainerInterface $container): CreativeRepositoryInterface
    {
        $creatives = $container->get(CreativeRepositoryInterface::class);

        if (!$creatives instanceof CreativeRepositoryInterface) {
            throw new LogicException('Creative repository service is invalid.');
        }

        return $creatives;
    }
}
