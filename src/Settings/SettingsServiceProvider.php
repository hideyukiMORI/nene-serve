<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Http\RuntimeServiceProvider;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Support\Crypto;
use NeneServe\Support\ServiceProviderHelpers;
use NeneServe\Tenant\UserRepositoryInterface;
use Psr\Container\ContainerInterface;

final readonly class SettingsServiceProvider implements ServiceProviderInterface
{
    use ServiceProviderHelpers;

    public const string ROUTE_REGISTRAR = 'nene-serve.route_registrar.settings';

    public const string EXCEPTION_HANDLER_ENCRYPTION = 'nene-serve.exception_handler.encryption_unavailable';

    public const string EXCEPTION_HANDLER_SMTP_NOT_CONFIGURED = 'nene-serve.exception_handler.smtp_not_configured';

    public const string EXCEPTION_HANDLER_SMTP_TEST_FAILED = 'nene-serve.exception_handler.smtp_test_failed';

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
                UpdateSmtpSettingsUseCaseInterface::class,
                static function (ContainerInterface $container): UpdateSmtpSettingsUseCaseInterface {
                    $settings = $container->get(SmtpSettingsRepositoryInterface::class);
                    $crypto = $container->get(Crypto::class);

                    if (!$settings instanceof SmtpSettingsRepositoryInterface) {
                        throw new LogicException('SMTP settings repository service is invalid.');
                    }

                    if (!$crypto instanceof Crypto) {
                        throw new LogicException('Crypto service is invalid.');
                    }

                    return new UpdateSmtpSettingsUseCase($settings, $crypto, self::transactions($container), self::orgId($container));
                },
            )
            ->set(
                UpdateSmtpSettingsHandler::class,
                static function (ContainerInterface $container): UpdateSmtpSettingsHandler {
                    $useCase = $container->get(UpdateSmtpSettingsUseCaseInterface::class);

                    if (!$useCase instanceof UpdateSmtpSettingsUseCaseInterface) {
                        throw new LogicException('Update SMTP settings use case service is invalid.');
                    }

                    return new UpdateSmtpSettingsHandler($useCase, self::json($container));
                },
            )
            ->set(
                TestSmtpSettingsUseCaseInterface::class,
                static function (ContainerInterface $container): TestSmtpSettingsUseCaseInterface {
                    $settings = $container->get(SmtpSettingsRepositoryInterface::class);
                    $crypto = $container->get(Crypto::class);
                    $mailerFactory = $container->get(MailerFactoryInterface::class);
                    $users = $container->get(UserRepositoryInterface::class);

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

                    return new TestSmtpSettingsUseCase($settings, $crypto, $mailerFactory, $users, self::transactions($container), self::orgId($container));
                },
            )
            ->set(
                TestSmtpSettingsHandler::class,
                static function (ContainerInterface $container): TestSmtpSettingsHandler {
                    $useCase = $container->get(TestSmtpSettingsUseCaseInterface::class);

                    if (!$useCase instanceof TestSmtpSettingsUseCaseInterface) {
                        throw new LogicException('Test SMTP settings use case service is invalid.');
                    }

                    return new TestSmtpSettingsHandler($useCase, self::json($container));
                },
            )
            ->set(
                self::EXCEPTION_HANDLER_ENCRYPTION,
                static fn (ContainerInterface $c): EncryptionUnavailableExceptionHandler => new EncryptionUnavailableExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_SMTP_NOT_CONFIGURED,
                static fn (ContainerInterface $c): SmtpNotConfiguredExceptionHandler => new SmtpNotConfiguredExceptionHandler(self::problem($c)),
            )
            ->set(
                self::EXCEPTION_HANDLER_SMTP_TEST_FAILED,
                static fn (ContainerInterface $c): SmtpTestFailedExceptionHandler => new SmtpTestFailedExceptionHandler(self::problem($c)),
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

    /** @return RequestScopedHolder<string> */
}
