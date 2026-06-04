<?php

declare(strict_types=1);

namespace NeneServe\Http;

use NeneServe\Http\Admin\CurrentUserHandler;
use NeneServe\Http\Admin\ListUsersHandler;
use NeneServe\Http\Admin\LoginHandler;
use NeneServe\Http\Auth\BearerTokenMiddleware;
use NeneServe\Http\Auth\Jwt;
use NeneServe\Http\Auth\ServiceTokenMiddleware;
use NeneServe\Http\Auth\UnauthorizedException;
use NeneServe\Http\PublicApi\RecordImpressionHandler;
use NeneServe\Http\PublicApi\RedirectClickHandler;
use NeneServe\Http\PublicApi\ServeHandler;
use NeneServe\Http\RateLimit\InMemoryRateLimiter;
use NeneServe\Http\RateLimit\RateLimiterInterface;
use NeneServe\Http\Service\ListPlacementsHandler;
use NeneServe\Service\Scope;
use NeneServe\Service\ServiceContext;
use NeneServe\Service\ServiceTokenRepositoryInterface;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\PlacementRepositoryInterface;
use NeneServe\Serving\Token\InMemoryTokenStore;
use NeneServe\Serving\Token\TokenStoreInterface;
use NeneServe\Serving\UseCase\ServeCreativeUseCase;
use NeneServe\Support\DevFixtures;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\Capability;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\UseCase\ListUsersUseCase;
use NeneServe\Tenant\UseCase\LoginUseCase;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Application kernel: wires dependencies, registers the three API surfaces, and
 * turns a {@see Request} into a {@see Response}. Transport-agnostic so the full
 * request lifecycle is unit-testable without a server (see tests/).
 *
 * Surfaces (ADR 0018), each fail closed:
 * - `/public/*` — no auth; origin-gated, rate-limited, opaque tokens only
 * - `/admin/*`  — JWT + Capability (ADR 0006)
 * - `/api/*`    — scoped service token
 * - `GET /health` — unauthenticated liveness
 *
 * Default dependencies use in-memory {@see DevFixtures}; production injects the
 * PDO repositories + a shared token/rate-limit store.
 */
final class Kernel
{
    public const VERSION = '0.1.0-scaffold';

    private readonly Router $router;
    private readonly JsonResponseFactory $json;
    private readonly BearerTokenMiddleware $auth;
    private readonly ServiceTokenMiddleware $serviceAuth;
    private readonly UserRepositoryInterface $users;
    private readonly OrganizationRepositoryInterface $organizations;
    private readonly PlacementRepositoryInterface $placements;
    private readonly CreativeRepositoryInterface $creatives;
    private readonly TokenStoreInterface $tokens;
    private readonly RateLimiterInterface $rateLimiter;
    private readonly Jwt $jwt;

    public function __construct(
        ?UserRepositoryInterface $users = null,
        ?OrganizationRepositoryInterface $organizations = null,
        ?Jwt $jwt = null,
        ?PlacementRepositoryInterface $placements = null,
        ?CreativeRepositoryInterface $creatives = null,
        ?TokenStoreInterface $tokens = null,
        ?RateLimiterInterface $rateLimiter = null,
        ?ServiceTokenRepositoryInterface $serviceTokens = null,
    ) {
        $this->json = new JsonResponseFactory();
        $this->users = $users ?? DevFixtures::users();
        $this->organizations = $organizations ?? DevFixtures::organizations();
        $this->placements = $placements ?? DevFixtures::placements();
        $this->creatives = $creatives ?? DevFixtures::creatives();
        $this->tokens = $tokens ?? new InMemoryTokenStore();
        $this->rateLimiter = $rateLimiter ?? new InMemoryRateLimiter();
        $this->jwt = $jwt ?? new Jwt(self::resolveSecret());
        $this->auth = new BearerTokenMiddleware($this->jwt, $this->users);
        $this->serviceAuth = new ServiceTokenMiddleware($serviceTokens ?? DevFixtures::serviceTokens());
        $this->router = new Router();
        $this->registerRoutes();
    }

    public function handle(Request $request): Response
    {
        $response = $this->router->dispatch($request);
        if ($response !== null) {
            return $response;
        }

        return $this->json->problem(
            404,
            'route-not-found',
            'Resource not found',
            sprintf('No route for %s %s.', $request->method, $request->path),
        );
    }

    private function registerRoutes(): void
    {
        // System — unauthenticated liveness (ADR 0018).
        $health = new HealthHandler($this->json);
        $this->router->add('GET', '/health', $health->show(...));

        $this->registerPublicRoutes();
        $this->registerAdminRoutes();
        $this->registerServiceRoutes();
    }

    /** Public serve surface `/public/*` — no auth; origin-gated, rate-limited. */
    private function registerPublicRoutes(): void
    {
        $serve = new ServeHandler(
            new ServeCreativeUseCase($this->placements, $this->creatives, $this->tokens, self::clickTokenTtl()),
            $this->rateLimiter,
            $this->json,
        );
        $this->router->add('GET', '/public/placements/{public_placement_key}/serve', $serve->handle(...));

        $impression = new RecordImpressionHandler($this->tokens, $this->rateLimiter, $this->json);
        $this->router->add('POST', '/public/events/impression', $impression->handle(...));

        $click = new RedirectClickHandler($this->tokens, $this->rateLimiter, $this->json);
        $this->router->add('GET', '/public/clicks/{click_token}', $click->handle(...));
    }

    /** Admin surface `/admin/*` — JWT + Capability (ADR 0006). */
    private function registerAdminRoutes(): void
    {
        $login = new LoginHandler(new LoginUseCase($this->organizations, $this->users, $this->jwt), $this->json);
        $this->router->add('POST', '/admin/login', $login->handle(...));

        $me = new CurrentUserHandler($this->users, $this->json);
        $this->router->add('GET', '/admin/me', $this->admin(null, $me->handle(...)));

        $listUsers = new ListUsersHandler(new ListUsersUseCase($this->users), $this->json);
        $this->router->add('GET', '/admin/users', $this->admin(Capability::ViewUsers, $listUsers->handle(...)));
    }

    /** Service surface `/api/*` — scoped service token (api-security §5). */
    private function registerServiceRoutes(): void
    {
        $listPlacements = new ListPlacementsHandler($this->placements, $this->json);
        $this->router->add('GET', '/api/placements', $this->service(Scope::ReadPlacements, $listPlacements->handle(...)));
    }

    /**
     * Admin guard: bearer JWT auth + optional capability. Fail closed: 401 on
     * auth failure, 403 on missing capability.
     *
     * @param callable(Request, AuthContext): Response $handler
     * @return callable(Request): Response
     */
    private function admin(?Capability $capability, callable $handler): callable
    {
        return function (Request $request) use ($capability, $handler): Response {
            try {
                $context = $this->auth->authenticate($request);
            } catch (UnauthorizedException) {
                return $this->json->problem(401, 'unauthorized', 'Authentication required');
            }

            if ($capability !== null && !$context->can($capability)) {
                return $this->json->problem(
                    403,
                    'insufficient-capability',
                    'Insufficient capability',
                    sprintf('This action requires the %s capability.', $capability->value),
                );
            }

            return $handler($request, $context);
        };
    }

    /**
     * Service guard: scoped service token. Fail closed: 401 on auth failure,
     * 403 `insufficient-scope` on missing scope.
     *
     * @param callable(Request, ServiceContext): Response $handler
     * @return callable(Request): Response
     */
    private function service(Scope $scope, callable $handler): callable
    {
        return function (Request $request) use ($scope, $handler): Response {
            try {
                $context = $this->serviceAuth->authenticate($request);
            } catch (UnauthorizedException) {
                return $this->json->problem(401, 'unauthorized', 'Authentication required');
            }

            if (!$context->hasScope($scope)) {
                return $this->json->problem(
                    403,
                    'insufficient-scope',
                    'Insufficient scope',
                    sprintf('This action requires the %s scope.', $scope->value),
                );
            }

            return $handler($request, $context);
        };
    }

    private static function resolveSecret(): string
    {
        $secret = getenv('NENE_SERVE_JWT_SECRET');
        if (is_string($secret) && $secret !== '') {
            return $secret;
        }

        // Local-only fallback so the scaffold boots without configuration.
        // Production MUST set NENE_SERVE_JWT_SECRET (api-security §6).
        return 'dev-insecure-secret-change-me';
    }

    private static function clickTokenTtl(): int
    {
        $ttl = getenv('NENE_SERVE_CLICK_TOKEN_TTL');

        return is_string($ttl) && ctype_digit($ttl) ? (int) $ttl : 900;
    }
}
