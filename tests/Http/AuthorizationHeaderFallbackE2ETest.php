<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use Nene2\Auth\TokenIssuerInterface;
use NeneServe\Http\RuntimeContainerFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * End-to-end proof that the opt-in X-Authorization fallback receiver (NENE2 #1558 /
 * ADR 0019) is wired into this product's runtime pipeline.
 *
 * Front-end fleet clients (`@hideyukimori/nene2-client` v1.1.0) mirror every bearer
 * token into `X-Authorization: Bearer <token>` so that shared hosting (HETEML-type
 * Tier A) — where an upstream proxy strips the standard `Authorization` header before
 * PHP sees it — can still authenticate. `RuntimeServiceProvider` enables the receiver
 * via `enableAuthorizationHeaderFallback: true`, so the framework's
 * AuthorizationHeaderFallbackMiddleware restores `Authorization` from the mirror
 * (only when `Authorization` is absent/empty) at the head of the auth stage, before
 * `AdminAuthMiddleware` runs.
 *
 * `GET /admin/users` with a token for the `analyst` role is the selected path: it is
 * Bearer-protected by `AdminAuthMiddleware`, needs no seeded tenant/organization row
 * (`TENANT_RESOLUTION` is unset in the unit test environment, so the pipeline stays in
 * Login mode and never reaches `OrgResolverMiddleware`, see `OrgResolutionMode`), and
 * `analyst` lacks the `ViewUsers` capability the route requires (mirrors the existing
 * `AdminAuthTest::testAuthenticatedUserLackingCapabilityIsForbidden` fixture). That
 * capability check runs in `CapabilityMiddleware`, strictly after `AdminAuthMiddleware`
 * accepts the token and before the route handler (and its database access) is ever
 * reached — so a deterministic `403 Forbidden` proves the credential passed the auth
 * stage without depending on any seeded row in the unit suite's schemaless in-memory
 * SQLite database (see phpunit.xml.dist).
 *
 * Unlike NENE2's generic BearerAuthMiddleware, `AdminAuthMiddleware` (ADR 0018) is a
 * product-specific implementation and never emits a `WWW-Authenticate` challenge; it
 * reports every rejection as an RFC 9457 `application/problem+json` body. These
 * assertions read the `detail` field to tell the auth stage's own missing/invalid
 * rejections apart from a response produced further down the pipeline.
 *
 * The tests fail if the opt-in flag is removed from RuntimeServiceProvider: a
 * mirror-only request would then never restore `Authorization`, so `AdminAuthMiddleware`
 * would reject it as "Authorization header must use the Bearer scheme." before
 * `CapabilityMiddleware` (or the verifier) ever runs.
 */
final class AuthorizationHeaderFallbackE2ETest extends TestCase
{
    private const PROTECTED_PATH = '/admin/users';

    private RequestHandlerInterface $app;
    private TokenIssuerInterface $issuer;

    protected function setUp(): void
    {
        parent::setUp();

        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        $app = $container->get(RequestHandlerInterface::class);
        self::assertInstanceOf(RequestHandlerInterface::class, $app);
        $this->app = $app;

        $issuer = $container->get(TokenIssuerInterface::class);
        self::assertInstanceOf(TokenIssuerInterface::class, $issuer);
        $this->issuer = $issuer;
    }

    /**
     * The mirror end-to-end proof: a valid bearer token supplied ONLY in the
     * `X-Authorization` header (no standard `Authorization`) is restored by the
     * fallback receiver, verified by `AdminAuthMiddleware`, and reaches
     * `CapabilityMiddleware` — which rejects the `analyst` role with a capability
     * `403`, not an authentication `401`. That specific outcome is only reachable
     * once the token has been authenticated, so it proves the mirror-only request
     * passed the auth stage.
     */
    public function test_valid_token_in_mirror_only_passes_authentication(): void
    {
        $token = $this->issuer->issue(['sub' => 'u-analyst', 'org' => 'org-acme', 'role' => 'analyst', 'exp' => time() + 3600]);

        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('X-Authorization', 'Bearer ' . $token);

        $response = $this->app->handle($request);

        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString(
            'https://nene2.dev/problems/forbidden',
            (string) json_decode((string) $response->getBody(), true)['type'],
        );
    }

    /**
     * The auth stage actually receives the mirrored credential: an INVALID token
     * in `X-Authorization` only is rejected with the verifier's own message
     * ("Token format is invalid: ...", from `LocalBearerTokenVerifier`), NOT
     * `AdminAuthMiddleware`'s "Authorization header must use the Bearer scheme."
     * — which is only possible if the fallback receiver restored `Authorization`
     * from the mirror before the verifier ran.
     */
    public function test_invalid_token_in_mirror_only_reaches_verifier_not_missing_header_check(): void
    {
        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('X-Authorization', 'Bearer not-a-real-token');

        $response = $this->app->handle($request);

        self::assertSame(401, $response->getStatusCode());
        /** @var array{detail?: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertStringContainsString('Token format is invalid', $body['detail'] ?? '');
        self::assertStringNotContainsString('must use the Bearer scheme', $body['detail'] ?? '');
    }

    /**
     * Baseline / control: with NO credential in either header, the auth stage
     * rejects with its own "Authorization header must use the Bearer scheme."
     * message. This is the response a mirror-only request would get if the
     * opt-in fallback were disabled.
     */
    public function test_no_credential_yields_missing_authorization_detail(): void
    {
        $request = (new Psr17Factory())->createServerRequest('GET', self::PROTECTED_PATH);

        $response = $this->app->handle($request);

        self::assertSame(401, $response->getStatusCode());
        /** @var array{detail?: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertStringContainsString('must use the Bearer scheme', $body['detail'] ?? '');
    }

    /**
     * The standard header still wins when both are present (byte-for-byte behaviour
     * unchanged on hosting that delivers `Authorization`): a valid standard token
     * authenticates (reaching the same capability `403`) even when an invalid mirror
     * is also sent. If the receiver wrongly preferred the mirror, the verifier would
     * reject the garbage token and this would be a `401` with "Token format is
     * invalid" instead.
     */
    public function test_standard_authorization_header_takes_precedence_over_mirror(): void
    {
        $token = $this->issuer->issue(['sub' => 'u-analyst', 'org' => 'org-acme', 'role' => 'analyst', 'exp' => time() + 3600]);

        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Authorization', 'Bearer not-a-real-token');

        $response = $this->app->handle($request);

        self::assertSame(403, $response->getStatusCode());
    }
}
