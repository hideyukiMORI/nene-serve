<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class SettingsServiceProvider implements ServiceProviderInterface
{
    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.settings';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                SmtpSettingsRepositoryInterface::class,
                static function (ContainerInterface $container): SmtpSettingsRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoSmtpSettingsRepository($query);
                },
            )
            ->set(
                GetSmtpSettingsHandler::class,
                static function (ContainerInterface $container): GetSmtpSettingsHandler {
                    $settings = $container->get(SmtpSettingsRepositoryInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$settings instanceof SmtpSettingsRepositoryInterface) {
                        throw new LogicException('SMTP settings repository service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new GetSmtpSettingsHandler($settings, $response, $problemDetails);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): SettingsRouteRegistrar {
                    $getSmtpHandler = $container->get(GetSmtpSettingsHandler::class);

                    if (!$getSmtpHandler instanceof GetSmtpSettingsHandler) {
                        throw new LogicException('Get SMTP settings handler service is invalid.');
                    }

                    return new SettingsRouteRegistrar($getSmtpHandler);
                },
            );
    }
}
