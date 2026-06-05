<?php

declare(strict_types=1);

namespace NeneServe\Http;

use LogicException;
use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Config\AppConfig;
use Nene2\Config\ConfigLoader;
use Nene2\Database\DatabaseConnectionFactoryInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Database\PdoDatabaseTransactionManager;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\ResponseEmitter;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Log\MonologLoggerFactory;
use Nene2\Log\RequestIdHolder;
use NeneServe\ApplicationServiceProvider;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Mail\SmtpMailerFactory;
use NeneServe\Marketplace\Invoice\FakeInvoiceClient;
use NeneServe\Marketplace\Invoice\HttpInvoiceClient;
use NeneServe\Marketplace\Invoice\InvoiceClientInterface;
use NeneServe\Measurement\EventStoreInterface;
use NeneServe\Measurement\PdoEventStore;
use NeneServe\Serving\Scan\BundleScannerInterface;
use NeneServe\Serving\Scan\ClamAvScanner;
use NeneServe\Serving\Scan\StubBundleScanner;
use NeneServe\Support\Crypto;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Auth\CapabilityMiddleware;
use NeneServe\Upstream\Deal\DealClientInterface;
use NeneServe\Upstream\Deal\FakeDealClient;
use NeneServe\Upstream\Deal\HttpDealClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Wires the NENE2 framework runtime for NeNe Serve: configuration, database,
 * PSR-17 factories, logging, and the {@see RuntimeApplicationFactory} that turns
 * the registered domain route registrars into a PSR-15 application.
 *
 * Domain providers (Phase 2) are added via {@see ApplicationServiceProvider};
 * authentication/capability and throttle middleware are wired as those domains
 * are ported.
 */
final readonly class RuntimeServiceProvider implements ServiceProviderInterface
{
    public const string PROJECT_ROOT = 'nene-serve.project_root';

    public function register(ContainerBuilder $builder): void
    {
        $builder->addProvider(new ApplicationServiceProvider());

        $builder
            ->set(
                ConfigLoader::class,
                static function (ContainerInterface $container): ConfigLoader {
                    $projectRoot = $container->get(self::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return new ConfigLoader($projectRoot);
                },
            )
            ->set(
                AppConfig::class,
                static function (ContainerInterface $container): AppConfig {
                    $loader = $container->get(ConfigLoader::class);

                    if (!$loader instanceof ConfigLoader) {
                        throw new LogicException('Config loader service is invalid.');
                    }

                    return $loader->load();
                },
            )
            ->set(
                DatabaseConnectionFactoryInterface::class,
                static function (ContainerInterface $container): DatabaseConnectionFactoryInterface {
                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return new PdoConnectionFactory($config->database);
                },
            )
            ->set(
                DatabaseQueryExecutorInterface::class,
                static function (ContainerInterface $container): DatabaseQueryExecutorInterface {
                    $connectionFactory = $container->get(DatabaseConnectionFactoryInterface::class);

                    if (!$connectionFactory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('Database connection factory service is invalid.');
                    }

                    return new PdoDatabaseQueryExecutor($connectionFactory);
                },
            )
            ->set(
                DatabaseTransactionManagerInterface::class,
                static function (ContainerInterface $container): DatabaseTransactionManagerInterface {
                    $connectionFactory = $container->get(DatabaseConnectionFactoryInterface::class);

                    if (!$connectionFactory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('Database connection factory service is invalid.');
                    }

                    return new PdoDatabaseTransactionManager($connectionFactory);
                },
            )
            ->set(
                AuditLogInterface::class,
                static function (ContainerInterface $container): AuditLogInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoAuditLog($query);
                },
            )
            ->set(
                EventStoreInterface::class,
                static function (ContainerInterface $container): EventStoreInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoEventStore($query);
                },
            )
            ->set(
                BundleScannerInterface::class,
                static function (ContainerInterface $container): BundleScannerInterface {
                    $host = getenv('CLAMAV_HOST');

                    if (is_string($host) && $host !== '') {
                        $port = getenv('CLAMAV_PORT');

                        return new ClamAvScanner($host, is_string($port) && $port !== '' ? (int) $port : 3310);
                    }

                    return new StubBundleScanner();
                },
            )
            ->set(
                DealClientInterface::class,
                static function (ContainerInterface $container): DealClientInterface {
                    $base = getenv('NENE_DEAL_API_BASE_URL');
                    $token = getenv('NENE_DEAL_SERVICE_TOKEN');

                    if (is_string($base) && $base !== '' && is_string($token) && $token !== '') {
                        return new HttpDealClient($base, $token);
                    }

                    return new FakeDealClient();
                },
            )
            ->set(
                InvoiceClientInterface::class,
                static function (ContainerInterface $container): InvoiceClientInterface {
                    $base = getenv('NENE_INVOICE_API_BASE_URL');
                    $token = getenv('NENE_INVOICE_SERVICE_TOKEN');

                    if (is_string($base) && $base !== '' && is_string($token) && $token !== '') {
                        return new HttpInvoiceClient($base, $token);
                    }

                    return new FakeInvoiceClient();
                },
            )
            ->set(Crypto::class, static fn (ContainerInterface $container): Crypto => new Crypto())
            ->set(
                MailerFactoryInterface::class,
                static fn (ContainerInterface $container): MailerFactoryInterface => new SmtpMailerFactory(),
            )
            ->set(Psr17Factory::class, static fn (ContainerInterface $container): Psr17Factory => new Psr17Factory())
            ->set(
                ResponseFactoryInterface::class,
                static function (ContainerInterface $container): ResponseFactoryInterface {
                    $factory = $container->get(Psr17Factory::class);

                    if (!$factory instanceof ResponseFactoryInterface) {
                        throw new LogicException('PSR-17 response factory service is invalid.');
                    }

                    return $factory;
                },
            )
            ->set(
                StreamFactoryInterface::class,
                static function (ContainerInterface $container): StreamFactoryInterface {
                    $factory = $container->get(Psr17Factory::class);

                    if (!$factory instanceof StreamFactoryInterface) {
                        throw new LogicException('PSR-17 stream factory service is invalid.');
                    }

                    return $factory;
                },
            )
            ->set(
                JsonResponseFactory::class,
                static function (ContainerInterface $container): JsonResponseFactory {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    return new JsonResponseFactory($responseFactory, $streamFactory);
                },
            )
            ->set(
                ProblemDetailsResponseFactory::class,
                static function (ContainerInterface $container): ProblemDetailsResponseFactory {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);
                    $config = $container->get(AppConfig::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return new ProblemDetailsResponseFactory($responseFactory, $streamFactory, $config->problemDetailsBaseUrl);
                },
            )
            ->set(RequestIdHolder::class, static fn (ContainerInterface $container): RequestIdHolder => new RequestIdHolder())
            ->set(
                LoggerInterface::class,
                static function (ContainerInterface $container): LoggerInterface {
                    $config = $container->get(AppConfig::class);
                    $debug = $config instanceof AppConfig && $config->debug;
                    $holder = $container->get(RequestIdHolder::class);

                    return (new MonologLoggerFactory())->create(
                        'nene-serve',
                        $debug,
                        $holder instanceof RequestIdHolder ? $holder : null,
                    );
                },
            )
            ->set(
                LocalBearerTokenVerifier::class,
                static function (ContainerInterface $container): LocalBearerTokenVerifier {
                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return new LocalBearerTokenVerifier($config->localJwtSecret ?? 'nene-serve-dev-secret');
                },
            )
            ->set(
                TokenVerifierInterface::class,
                static function (ContainerInterface $container): TokenVerifierInterface {
                    $verifier = $container->get(LocalBearerTokenVerifier::class);

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('LocalBearerTokenVerifier service is invalid.');
                    }

                    return $verifier;
                },
            )
            ->set(
                TokenIssuerInterface::class,
                static function (ContainerInterface $container): TokenIssuerInterface {
                    $issuer = $container->get(LocalBearerTokenVerifier::class);

                    if (!$issuer instanceof TokenIssuerInterface) {
                        throw new LogicException('LocalBearerTokenVerifier service is invalid.');
                    }

                    return $issuer;
                },
            )
            ->set(
                AdminAuthMiddleware::class,
                static function (ContainerInterface $container): AdminAuthMiddleware {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    $verifier = $container->get(TokenVerifierInterface::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('Token verifier service is invalid.');
                    }

                    return new AdminAuthMiddleware($problemDetails, $verifier);
                },
            )
            ->set(
                CapabilityMiddleware::class,
                static function (ContainerInterface $container): CapabilityMiddleware {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new CapabilityMiddleware($problemDetails);
                },
            )
            ->set(
                RuntimeApplicationFactory::class,
                static function (ContainerInterface $container): RuntimeApplicationFactory {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $streamFactory = $container->get(StreamFactoryInterface::class);
                    $logger = $container->get(LoggerInterface::class);
                    $config = $container->get(AppConfig::class);
                    $requestIdHolder = $container->get(RequestIdHolder::class);
                    $adminAuth = $container->get(AdminAuthMiddleware::class);
                    $capability = $container->get(CapabilityMiddleware::class);
                    $exceptionHandlers = $container->get(ApplicationServiceProvider::EXCEPTION_HANDLERS);
                    $routeRegistrars = $container->get(ApplicationServiceProvider::ROUTE_REGISTRARS);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Response factory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('Stream factory service is invalid.');
                    }

                    if (!$logger instanceof LoggerInterface) {
                        throw new LogicException('Logger service is invalid.');
                    }

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    if (!$requestIdHolder instanceof RequestIdHolder) {
                        throw new LogicException('RequestIdHolder service is invalid.');
                    }

                    if (!$adminAuth instanceof AdminAuthMiddleware) {
                        throw new LogicException('Admin auth middleware service is invalid.');
                    }

                    if (!$capability instanceof CapabilityMiddleware) {
                        throw new LogicException('Capability middleware service is invalid.');
                    }

                    if (!is_array($exceptionHandlers) || !array_is_list($exceptionHandlers)) {
                        throw new LogicException('Exception handlers service is invalid.');
                    }

                    if (!is_array($routeRegistrars) || !array_is_list($routeRegistrars)) {
                        throw new LogicException('Route registrars service is invalid.');
                    }

                    /** @var list<DomainExceptionHandlerInterface> $exceptionHandlers */
                    /** @var list<callable(\Nene2\Routing\Router): void> $routeRegistrars */

                    return new RuntimeApplicationFactory(
                        responseFactory: $responseFactory,
                        streamFactory: $streamFactory,
                        logger: $logger,
                        machineApiKey: $config->machineApiKey,
                        domainExceptionHandlers: $exceptionHandlers,
                        requestIdHolder: $requestIdHolder,
                        routeRegistrars: $routeRegistrars,
                        authMiddleware: [$adminAuth, $capability],
                        debug: $config->debug,
                        requestMaxBodyBytes: 10 * 1024 * 1024,
                        problemDetailsBaseUrl: $config->problemDetailsBaseUrl,
                    );
                },
            )
            ->set(
                RequestHandlerInterface::class,
                static function (ContainerInterface $container): RequestHandlerInterface {
                    $factory = $container->get(RuntimeApplicationFactory::class);

                    if (!$factory instanceof RuntimeApplicationFactory) {
                        throw new LogicException('Runtime application factory service is invalid.');
                    }

                    return $factory->create();
                },
            )
            ->set(ResponseEmitter::class, static fn (ContainerInterface $container): ResponseEmitter => new ResponseEmitter());
    }
}
