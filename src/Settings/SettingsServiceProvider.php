<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
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
                static fn (ContainerInterface $c): SmtpSettingsRepositoryInterface => new PdoSmtpSettingsRepository(self::query($c)),
            )
            ->set(
                GetSmtpSettingsUseCaseInterface::class,
                static fn (ContainerInterface $c): GetSmtpSettingsUseCaseInterface => new GetSmtpSettingsUseCase(self::service($c, SmtpSettingsRepositoryInterface::class), self::orgId($c)),
            )
            ->set(
                GetSmtpSettingsHandler::class,
                static fn (ContainerInterface $c): GetSmtpSettingsHandler => new GetSmtpSettingsHandler(self::service($c, GetSmtpSettingsUseCaseInterface::class), self::json($c)),
            )
            ->set(
                UpdateSmtpSettingsUseCaseInterface::class,
                static fn (ContainerInterface $c): UpdateSmtpSettingsUseCaseInterface => new UpdateSmtpSettingsUseCase(
                    self::service($c, SmtpSettingsRepositoryInterface::class),
                    self::service($c, Crypto::class),
                    self::transactions($c),
                    self::orgId($c),
                    self::dialect($c),
                ),
            )
            ->set(
                UpdateSmtpSettingsHandler::class,
                static fn (ContainerInterface $c): UpdateSmtpSettingsHandler => new UpdateSmtpSettingsHandler(self::service($c, UpdateSmtpSettingsUseCaseInterface::class), self::json($c)),
            )
            ->set(
                TestSmtpSettingsUseCaseInterface::class,
                static fn (ContainerInterface $c): TestSmtpSettingsUseCaseInterface => new TestSmtpSettingsUseCase(
                    self::service($c, SmtpSettingsRepositoryInterface::class),
                    self::service($c, Crypto::class),
                    self::service($c, MailerFactoryInterface::class),
                    self::service($c, UserRepositoryInterface::class),
                    self::transactions($c),
                    self::orgId($c),
                ),
            )
            ->set(
                TestSmtpSettingsHandler::class,
                static fn (ContainerInterface $c): TestSmtpSettingsHandler => new TestSmtpSettingsHandler(self::service($c, TestSmtpSettingsUseCaseInterface::class), self::json($c)),
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
                static fn (ContainerInterface $c): SettingsRouteRegistrar => new SettingsRouteRegistrar(
                    self::service($c, GetSmtpSettingsHandler::class),
                    self::service($c, UpdateSmtpSettingsHandler::class),
                    self::service($c, TestSmtpSettingsHandler::class),
                ),
            );
    }
}
