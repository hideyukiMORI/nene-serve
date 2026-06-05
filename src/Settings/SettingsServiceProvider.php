<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Http\RuntimeServiceProvider;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Support\Crypto;
use NeneServe\Tenant\UserRepositoryInterface;
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
                GetSmtpSettingsUseCaseInterface::class,
                static function (ContainerInterface $container): GetSmtpSettingsUseCaseInterface {
                    $settings = $container->get(SmtpSettingsRepositoryInterface::class);
                    $organizationId = $container->get(RuntimeServiceProvider::ORG_ID_HOLDER);

                    if (!$settings instanceof SmtpSettingsRepositoryInterface) {
                        throw new LogicException('SMTP settings repository service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new GetSmtpSettingsUseCase($settings, $organizationId);
                },
            )
            ->set(
                GetSmtpSettingsHandler::class,
                static function (ContainerInterface $container): GetSmtpSettingsHandler {
                    $useCase = $container->get(GetSmtpSettingsUseCaseInterface::class);
                    $response = $container->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetSmtpSettingsUseCaseInterface) {
                        throw new LogicException('Get SMTP settings use case service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetSmtpSettingsHandler($useCase, $response);
                },
            )
            ->set(
                UpdateSmtpSettingsHandler::class,
                static function (ContainerInterface $container): UpdateSmtpSettingsHandler {
                    $settings = $container->get(SmtpSettingsRepositoryInterface::class);
                    $crypto = $container->get(Crypto::class);
                    $audit = $container->get(AuditLogInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$settings instanceof SmtpSettingsRepositoryInterface) {
                        throw new LogicException('SMTP settings repository service is invalid.');
                    }

                    if (!$crypto instanceof Crypto) {
                        throw new LogicException('Crypto service is invalid.');
                    }

                    if (!$audit instanceof AuditLogInterface) {
                        throw new LogicException('Audit log service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new UpdateSmtpSettingsHandler($settings, $crypto, $audit, $response, $problemDetails);
                },
            )
            ->set(
                TestSmtpSettingsHandler::class,
                static function (ContainerInterface $container): TestSmtpSettingsHandler {
                    $settings = $container->get(SmtpSettingsRepositoryInterface::class);
                    $crypto = $container->get(Crypto::class);
                    $mailerFactory = $container->get(MailerFactoryInterface::class);
                    $users = $container->get(UserRepositoryInterface::class);
                    $audit = $container->get(AuditLogInterface::class);
                    $response = $container->get(JsonResponseFactory::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$settings instanceof SmtpSettingsRepositoryInterface) {
                        throw new LogicException('SMTP settings repository service is invalid.');
                    }

                    if (!$crypto instanceof Crypto) {
                        throw new LogicException('Crypto service is invalid.');
                    }

                    if (!$mailerFactory instanceof MailerFactoryInterface) {
                        throw new LogicException('Mailer factory service is invalid.');
                    }

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('User repository service is invalid.');
                    }

                    if (!$audit instanceof AuditLogInterface) {
                        throw new LogicException('Audit log service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new TestSmtpSettingsHandler($settings, $crypto, $mailerFactory, $users, $audit, $response, $problemDetails);
                },
            )
            ->set(
                self::ROUTE_REGISTRAR,
                static function (ContainerInterface $container): SettingsRouteRegistrar {
                    $getSmtpHandler = $container->get(GetSmtpSettingsHandler::class);
                    $updateSmtpHandler = $container->get(UpdateSmtpSettingsHandler::class);
                    $testSmtpHandler = $container->get(TestSmtpSettingsHandler::class);

                    if (!$getSmtpHandler instanceof GetSmtpSettingsHandler) {
                        throw new LogicException('Get SMTP settings handler service is invalid.');
                    }

                    if (!$updateSmtpHandler instanceof UpdateSmtpSettingsHandler) {
                        throw new LogicException('Update SMTP settings handler service is invalid.');
                    }

                    if (!$testSmtpHandler instanceof TestSmtpSettingsHandler) {
                        throw new LogicException('Test SMTP settings handler service is invalid.');
                    }

                    return new SettingsRouteRegistrar($getSmtpHandler, $updateSmtpHandler, $testSmtpHandler);
                },
            );
    }
}
