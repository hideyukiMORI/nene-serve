<?php

declare(strict_types=1);

namespace NeneServe\Http;

use NeneServe\Http\Admin\CurrentUserHandler;
use NeneServe\Http\Admin\ListUsersHandler;
use NeneServe\Http\Admin\LoginHandler;
use NeneServe\Http\Auth\BearerTokenMiddleware;
use NeneServe\Http\Auth\Jwt;
use NeneServe\Http\Auth\UnauthorizedException;
use NeneServe\Support\DevFixtures;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\Capability;
use NeneServe\Tenant\OrganizationRepositoryInterface;
use NeneServe\Tenant\UseCase\ListUsersUseCase;
use NeneServe\Tenant\UseCase\LoginUseCase;
use NeneServe\Tenant\UserRepositoryInterface;

/**
 * Application kernel: wires dependencies, registers routes, and turns a
 * {@see Request} into a {@see Response}. Transport-agnostic so the full request
 * lifecycle is unit-testable without a server (see tests/).
 *
 * Default dependencies use in-memory {@see DevFixtures}; production injects the
 * PDO repositories (#12). The full three-surface routing, CORS, and rate limits
 * also land in #12 — this kernel establishes the admin auth model (ADR 0006/0018).
 */
final class Kernel
{
    public const VERSION = '0.1.0-scaffold';

    private readonly Router $router;
    private readonly JsonResponseFactory $json;
    private readonly BearerTokenMiddleware $auth;
    private readonly UserRepositoryInterface $users;
    private readonly OrganizationRepositoryInterface $organizations;
    private readonly Jwt $jwt;

    public function __construct(
        ?UserRepositoryInterface $users = null,
        ?OrganizationRepositoryInterface $organizations = null,
        ?Jwt $jwt = null,
    ) {
        $this->json = new JsonResponseFactory();
        $this->users = $users ?? DevFixtures::users();
        $this->organizations = $organizations ?? DevFixtures::organizations();
        $this->jwt = $jwt ?? new Jwt(self::resolveSecret());
        $this->auth = new BearerTokenMiddleware($this->jwt, $this->users);
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

        // Admin auth — unauthenticated entry point.
        $login = new LoginHandler(new LoginUseCase($this->organizations, $this->users, $this->jwt), $this->json);
        $this->router->add('POST', '/admin/login', $login->handle(...));

        // Admin — JWT required; any authenticated role.
        $me = new CurrentUserHandler($this->users, $this->json);
        $this->router->add('GET', '/admin/me', $this->admin(null, $me->handle(...)));

        // Admin — JWT + `view_users` capability; tenant-scoped result.
        $listUsers = new ListUsersHandler(new ListUsersUseCase($this->users), $this->json);
        $this->router->add('GET', '/admin/users', $this->admin(Capability::ViewUsers, $listUsers->handle(...)));
    }

    /**
     * Wraps an admin handler with bearer authentication and optional capability
     * gating. Fail closed: 401 on auth failure, 403 on missing capability
     * (api-security §4, Problem Details slugs `unauthorized` /
     * `insufficient-capability`).
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
}
