<?php

declare(strict_types=1);

namespace NeneServe\Http;

use LogicException;
use Nene2\Auth\GuardedJwtSecretResolver;
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
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\ResponseEmitter;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use Nene2\Log\MonologLoggerFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Middleware\InMemoryRateLimitStorage;
use Nene2\Middleware\RateLimitStorageInterface;
use Nene2\Middleware\ThrottleMiddleware;
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
use NeneServe\Service\Auth\ScopeMiddleware;
use NeneServe\Service\Auth\ServiceAuthMiddleware;
use NeneServe\Service\PdoServiceTokenRepository;
use NeneServe\Service\ServiceTokenRepositoryInterface;
use NeneServe\Serving\Frequency\FileFrequencyCapStore;
use NeneServe\Serving\Frequency\FrequencyCapStoreInterface;
use NeneServe\Serving\Scan\BundleScannerInterface;
use NeneServe\Serving\Scan\ClamAvScanner;
use NeneServe\Serving\Scan\StubBundleScanner;
use NeneServe\Serving\Token\FileTokenStore;
use NeneServe\Serving\Token\TokenStoreInterface;
use NeneServe\Storage\LocalStorage;
use NeneServe\Storage\StorageInterface;
use NeneServe\Support\Crypto;
use NeneServe\Support\SqlDialect;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Auth\CapabilityMiddleware;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\Resolution\CustomDomainResolutionStrategy;
use NeneServe\Tenant\Resolution\EnvResolutionStrategy;
use NeneServe\Tenant\Resolution\OrgResolutionMode;
use NeneServe\Tenant\Resolution\OrgResolutionStrategyInterface;
use NeneServe\Tenant\Resolution\OrgResolverMiddleware;
use NeneServe\Tenant\Resolution\PathPrefixResolutionStrategy;
use NeneServe\Tenant\Resolution\SubdomainResolutionStrategy;
use NeneServe\Upstream\Deal\DealClientInterface;
use NeneServe\Upstream\Deal\FakeDealClient;
use NeneServe\Upstream\Deal\HttpDealClient;
use NeneServe\Upstream\Records\FakeRecordsClient;
use NeneServe\Upstream\Records\HttpRecordsClient;
use NeneServe\Upstream\Records\RecordsClientInterface;
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

    /**
     * Product-injected development secret for the local JWT signing key (#136, #138).
     * The framework never owns a dev secret; {@see GuardedJwtSecretResolver} only uses
     * this when {@see AppConfig::$allowDevSecret} is opted in outside production, and
     * refuses to boot otherwise (fail-closed).
     */
    private const string DEFAULT_DEV_SECRET = 'nene-serve-dev-secret';

    /**
     * This repo's documented local JWT secret env key (docs/explanation/terminology.md,
     * .env.example). It differs from the shared NENE2_LOCAL_JWT_SECRET key that
     * {@see ConfigLoader} reads by default, so serve reads it directly and passes it to
     * {@see GuardedJwtSecretResolver} as the configured secret (and as `secretEnvName`
     * so fail-closed messages name the correct variable).
     */
    private const string JWT_SECRET_ENV_KEY = 'NENE_SERVE_JWT_SECRET';

    /**
     * Shared {@see RequestScopedHolder}<string> carrying the authenticated tenant
     * for the admin and service surfaces: the auth middleware writes it, admin/
     * service use-cases read it. (The public surface derives its tenant from the
     * placement key, so it never reads this holder.)
     */
    public const string ORG_ID_HOLDER = 'nene-serve.org_id_holder';

    public function register(ContainerBuilder $builder): void
    {
        $builder->addProvider(new ApplicationServiceProvider());

        $builder
            ->set(
                self::ORG_ID_HOLDER,
                static fn (ContainerInterface $container): RequestScopedHolder => new RequestScopedHolder(),
            )
            ->set(
                ClockInterface::class,
                static fn (ContainerInterface $container): ClockInterface => new UtcClock(),
            )
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
                SqlDialect::class,
                static function (ContainerInterface $container): SqlDialect {
                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return SqlDialect::fromAdapter($config->database->adapter);
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
                    $clock = $container->get(ClockInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new PdoAuditLog($query, $clock);
                },
            )
            ->set(
                EventStoreInterface::class,
                static function (ContainerInterface $container): EventStoreInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    $dialect = $container->get(SqlDialect::class);
                    $clock = $container->get(ClockInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$dialect instanceof SqlDialect) {
                        throw new LogicException('SQL dialect service is invalid.');
                    }

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new PdoEventStore($query, $dialect, $clock);
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
            ->set(
                RecordsClientInterface::class,
                static function (ContainerInterface $container): RecordsClientInterface {
                    $base = getenv('NENE_RECORDS_API_BASE_URL');
                    $token = getenv('NENE_RECORDS_SERVICE_TOKEN');

                    if (is_string($base) && $base !== '' && is_string($token) && $token !== '') {
                        return new HttpRecordsClient($base, $token);
                    }

                    return new FakeRecordsClient();
                },
            )
            ->set(
                StorageInterface::class,
                static function (ContainerInterface $container): StorageInterface {
                    $projectRoot = $container->get(self::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return new LocalStorage($projectRoot . '/var/uploads');
                },
            )
            ->set(
                TokenStoreInterface::class,
                static function (ContainerInterface $container): TokenStoreInterface {
                    $projectRoot = $container->get(self::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    $clock = $container->get(ClockInterface::class);

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new FileTokenStore($projectRoot . '/var/tokens.json', $clock);
                },
            )
            ->set(
                FrequencyCapStoreInterface::class,
                static function (ContainerInterface $container): FrequencyCapStoreInterface {
                    $projectRoot = $container->get(self::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return new FileFrequencyCapStore($projectRoot . '/var/frequency.json');
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

                    // Serve reads its JWT secret under a custom key
                    // (NENE_SERVE_JWT_SECRET), so it drives the resolver's full
                    // constructor directly instead of the NENE2_LOCAL_JWT_SECRET
                    // convenience path. The resolver fails closed: production always
                    // requires the secret, and local/test only accepts the injected
                    // dev secret behind the NENE2_ALLOW_DEV_SECRET opt-in.
                    $configuredSecret = $_SERVER[self::JWT_SECRET_ENV_KEY]
                        ?? $_ENV[self::JWT_SECRET_ENV_KEY]
                        ?? '';

                    return new LocalBearerTokenVerifier(
                        (new GuardedJwtSecretResolver(
                            configuredSecret: is_string($configuredSecret) ? $configuredSecret : '',
                            environment: $config->environment,
                            allowDevSecret: $config->allowDevSecret,
                            devSecret: self::DEFAULT_DEV_SECRET,
                            secretEnvName: self::JWT_SECRET_ENV_KEY,
                        ))->resolve(),
                    );
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
                    $organizationId = $container->get(self::ORG_ID_HOLDER);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('Token verifier service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new AdminAuthMiddleware($problemDetails, $verifier, $organizationId);
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
                OrgResolutionMode::class,
                static fn (ContainerInterface $container): OrgResolutionMode => self::tenantResolutionMode(),
            )
            ->set(
                OrgResolverMiddleware::class,
                static function (ContainerInterface $container): OrgResolverMiddleware {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    $organizations = $container->get(OrganizationRepositoryInterface::class);
                    $organizationId = $container->get(self::ORG_ID_HOLDER);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    if (!$organizations instanceof OrganizationRepositoryInterface) {
                        throw new LogicException('Organization repository service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    $mode = self::tenantResolutionMode();

                    return new OrgResolverMiddleware(
                        $organizationId,
                        $organizations,
                        $problemDetails,
                        self::resolutionStrategy($mode),
                        $mode,
                    );
                },
            )
            ->set(
                ServiceTokenRepositoryInterface::class,
                static function (ContainerInterface $container): ServiceTokenRepositoryInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoServiceTokenRepository($query);
                },
            )
            ->set(
                ServiceAuthMiddleware::class,
                static function (ContainerInterface $container): ServiceAuthMiddleware {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    $tokens = $container->get(ServiceTokenRepositoryInterface::class);
                    $organizationId = $container->get(self::ORG_ID_HOLDER);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    if (!$tokens instanceof ServiceTokenRepositoryInterface) {
                        throw new LogicException('Service token repository service is invalid.');
                    }

                    if (!$organizationId instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    return new ServiceAuthMiddleware($problemDetails, $tokens, $organizationId);
                },
            )
            ->set(
                ScopeMiddleware::class,
                static function (ContainerInterface $container): ScopeMiddleware {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    return new ScopeMiddleware($problemDetails);
                },
            )
            ->set(
                RateLimitStorageInterface::class,
                static fn (ContainerInterface $container): RateLimitStorageInterface => new InMemoryRateLimitStorage(),
            )
            ->set(
                ThrottleMiddleware::class,
                static function (ContainerInterface $container): ThrottleMiddleware {
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);
                    $storage = $container->get(RateLimitStorageInterface::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    if (!$storage instanceof RateLimitStorageInterface) {
                        throw new LogicException('Rate limit storage service is invalid.');
                    }

                    // Generous fixed-window budget for the untrusted public surface;
                    // production must inject a shared (Redis/DB) storage so the limit
                    // is enforced across PHP-FPM workers.
                    return new ThrottleMiddleware($problemDetails, $storage, limit: 600, windowSeconds: 60);
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
                    $serviceAuth = $container->get(ServiceAuthMiddleware::class);
                    $scope = $container->get(ScopeMiddleware::class);
                    $throttle = $container->get(ThrottleMiddleware::class);
                    $tenantMode = self::tenantResolutionMode();
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

                    if (!$serviceAuth instanceof ServiceAuthMiddleware) {
                        throw new LogicException('Service auth middleware service is invalid.');
                    }

                    if (!$scope instanceof ScopeMiddleware) {
                        throw new LogicException('Scope middleware service is invalid.');
                    }

                    if (!$throttle instanceof ThrottleMiddleware) {
                        throw new LogicException('Throttle middleware service is invalid.');
                    }

                    if (!is_array($exceptionHandlers) || !array_is_list($exceptionHandlers)) {
                        throw new LogicException('Exception handlers service is invalid.');
                    }

                    if (!is_array($routeRegistrars) || !array_is_list($routeRegistrars)) {
                        throw new LogicException('Route registrars service is invalid.');
                    }

                    /** @var list<DomainExceptionHandlerInterface> $exceptionHandlers */
                    /** @var list<callable(\Nene2\Routing\Router): void> $routeRegistrars */

                    $authMiddleware = [$adminAuth, $capability, $serviceAuth, $scope];

                    // URL resolution modes (subdomain / path / custom domain /
                    // single) derive the tenant from the request before auth runs;
                    // login mode keeps the JWT-only pipeline unchanged (ADR 0006).
                    if ($tenantMode->usesUrlResolution()) {
                        $orgResolver = $container->get(OrgResolverMiddleware::class);

                        if (!$orgResolver instanceof OrgResolverMiddleware) {
                            throw new LogicException('Org resolver middleware service is invalid.');
                        }

                        array_unshift($authMiddleware, $orgResolver);
                    }

                    return new RuntimeApplicationFactory(
                        responseFactory: $responseFactory,
                        streamFactory: $streamFactory,
                        logger: $logger,
                        machineApiKey: $config->machineApiKey,
                        domainExceptionHandlers: $exceptionHandlers,
                        requestIdHolder: $requestIdHolder,
                        routeRegistrars: $routeRegistrars,
                        authMiddleware: $authMiddleware,
                        throttleMiddleware: $throttle,
                        debug: $config->debug,
                        requestMaxBodyBytes: 10 * 1024 * 1024,
                        problemDetailsBaseUrl: $config->problemDetailsBaseUrl,
                        // Opt-in の X-Authorization フォールバック受け口（NENE2 #1558・ADR 0019）。
                        // 前段 proxy が標準 Authorization を剥がす共有ホスティング（HETEML 型 Tier A）で、
                        // nene2-js v1.1.0 が全リクエストに付与する `X-Authorization: Bearer` ミラーを
                        // Authorization 不在/空のときのみ採用する。標準ヘッダが届く環境ではバイト不変。
                        enableAuthorizationHeaderFallback: true,
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

    /**
     * The configured tenant resolution mode (`TENANT_RESOLUTION`), defaulting to
     * {@see OrgResolutionMode::Login} when unset or unrecognized (ADR 0006).
     */
    private static function tenantResolutionMode(): OrgResolutionMode
    {
        $value = getenv('TENANT_RESOLUTION');

        return OrgResolutionMode::fromEnv(is_string($value) ? $value : null);
    }

    private static function resolutionStrategy(OrgResolutionMode $mode): OrgResolutionStrategyInterface
    {
        return match ($mode) {
            OrgResolutionMode::Subdomain => new SubdomainResolutionStrategy(self::env('TENANT_BASE_DOMAIN', 'localhost')),
            OrgResolutionMode::Path => new PathPrefixResolutionStrategy(),
            OrgResolutionMode::CustomDomain => new CustomDomainResolutionStrategy(),
            // Single (fixed slug) — and Login, which never reaches the pipeline.
            default => new EnvResolutionStrategy(self::env('TENANT_ORG_SLUG', '')),
        };
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
