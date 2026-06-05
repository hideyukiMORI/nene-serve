<?php

declare(strict_types=1);

namespace NeneServe\Support;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Http\RuntimeServiceProvider;
use Psr\Container\ContainerInterface;

/**
 * Shared container-service accessors for domain {@see \Nene2\DependencyInjection\ServiceProviderInterface}
 * implementations. Each returns a validated, typed service (or throws a
 * {@see LogicException}), so the per-provider factory closures stay terse.
 */
trait ServiceProviderHelpers
{
    protected static function query(ContainerInterface $container): DatabaseQueryExecutorInterface
    {
        $query = $container->get(DatabaseQueryExecutorInterface::class);

        if (!$query instanceof DatabaseQueryExecutorInterface) {
            throw new LogicException('Database query executor service is invalid.');
        }

        return $query;
    }

    protected static function transactions(ContainerInterface $container): DatabaseTransactionManagerInterface
    {
        $transactions = $container->get(DatabaseTransactionManagerInterface::class);

        if (!$transactions instanceof DatabaseTransactionManagerInterface) {
            throw new LogicException('Database transaction manager service is invalid.');
        }

        return $transactions;
    }

    /** @return RequestScopedHolder<string> */
    protected static function orgId(ContainerInterface $container): RequestScopedHolder
    {
        $orgId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

        if (!$orgId instanceof RequestScopedHolder) {
            throw new LogicException('Organization id holder service is invalid.');
        }

        return $orgId;
    }

    protected static function json(ContainerInterface $container): JsonResponseFactory
    {
        $json = $container->get(JsonResponseFactory::class);

        if (!$json instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $json;
    }

    protected static function problem(ContainerInterface $container): ProblemDetailsResponseFactory
    {
        $problem = $container->get(ProblemDetailsResponseFactory::class);

        if (!$problem instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $problem;
    }
}
