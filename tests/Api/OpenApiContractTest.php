<?php

declare(strict_types=1);

namespace NeneServe\Tests\Api;

use Nene2\Routing\Router;
use NeneServe\ApplicationServiceProvider;
use NeneServe\Http\RuntimeContainerFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * The OpenAPI 3.1 documents in docs/api/ are the binding contract for the three
 * surfaces (ADR 0018). This test keeps the contract from drifting away from the
 * implementation: it asserts the three specs are well-formed OpenAPI 3.1 and that
 * the set of documented {method, path} pairs is EXACTLY the set of routes the
 * NENE2 runtime actually registers — every documented operation is routed, and
 * every route is documented.
 */
final class OpenApiContractTest extends TestCase
{
    private const SPECS = [
        'public.openapi.json',
        'admin.openapi.json',
        'service.openapi.json',
    ];

    /** @return array<string, mixed> */
    private function spec(string $name): array
    {
        $path = dirname(__DIR__, 2) . '/docs/api/' . $name;
        self::assertFileExists($path);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /** @return list<string> "METHOD /path/{param}" for every route the NENE2 runtime registers. */
    private function registeredRoutes(): array
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();
        $registrars = $container->get(ApplicationServiceProvider::ROUTE_REGISTRARS);
        self::assertIsArray($registrars);

        $router = new Router();
        foreach ($registrars as $registrar) {
            self::assertIsCallable($registrar);
            $registrar($router);
        }

        $routesProp = new ReflectionProperty(Router::class, 'routes');
        /** @var list<array{method: string, path: string}> $routes */
        $routes = $routesProp->getValue($router);

        $out = [];
        foreach ($routes as $route) {
            $out[] = $route['method'] . ' ' . $route['path'];
        }
        sort($out);

        return $out;
    }

    /** @return list<string> "METHOD /path/{param}" for every documented operation across all specs. */
    private function documentedRoutes(): array
    {
        $out = [];
        foreach (self::SPECS as $name) {
            /** @var array<string, array<string, mixed>> $paths */
            $paths = $this->spec($name)['paths'];
            foreach ($paths as $pattern => $operations) {
                foreach (array_keys($operations) as $method) {
                    $out[] = strtoupper((string) $method) . ' ' . (string) $pattern;
                }
            }
        }
        sort($out);

        return $out;
    }

    public function testEveryDocumentIsWellFormedOpenApi31(): void
    {
        foreach (self::SPECS as $name) {
            $spec = $this->spec($name);

            self::assertSame('3.1.0', $spec['openapi'] ?? null, "$name openapi version");
            self::assertIsArray($spec['info'] ?? null, "$name info");
            self::assertNotEmpty($spec['info']['title'] ?? null, "$name info.title");
            self::assertNotEmpty($spec['info']['version'] ?? null, "$name info.version");
            self::assertIsArray($spec['paths'] ?? null, "$name paths");
            self::assertNotEmpty($spec['paths'], "$name has paths");
        }
    }

    public function testEveryOperationHasAUniqueOperationId(): void
    {
        foreach (self::SPECS as $name) {
            /** @var array<string, array<string, mixed>> $paths */
            $paths = $this->spec($name)['paths'];
            $ids = [];
            foreach ($paths as $pattern => $operations) {
                foreach ($operations as $method => $op) {
                    self::assertIsArray($op);
                    self::assertNotEmpty($op['operationId'] ?? null, "$name $method $pattern operationId");
                    $ids[] = (string) $op['operationId'];
                }
            }
            self::assertSame(
                array_values(array_unique($ids)),
                $ids,
                "$name operationIds must be unique within the document",
            );
        }
    }

    public function testPublicDocumentIsSeparateAndUnauthenticated(): void
    {
        // ADR 0018: the public serve document is separate from admin/service and
        // carries no security scheme; admin/service each declare exactly one.
        $public = $this->spec('public.openapi.json');
        self::assertArrayNotHasKey('security', $public);
        self::assertArrayNotHasKey('securitySchemes', $public['components'] ?? []);

        foreach (['admin.openapi.json', 'service.openapi.json'] as $name) {
            $spec = $this->spec($name);
            self::assertNotEmpty($spec['security'] ?? null, "$name declares a default security requirement");
            self::assertCount(1, $spec['components']['securitySchemes'] ?? [], "$name has one security scheme");
        }
    }

    public function testEveryDocumentedOperationIsRouted(): void
    {
        $registered = $this->registeredRoutes();
        foreach ($this->documentedRoutes() as $documented) {
            self::assertContains(
                $documented,
                $registered,
                "Documented operation is not routed: $documented",
            );
        }
    }

    public function testEveryRegisteredRouteIsDocumented(): void
    {
        $documented = $this->documentedRoutes();
        foreach ($this->registeredRoutes() as $registered) {
            self::assertContains(
                $registered,
                $documented,
                "Registered route is missing from the OpenAPI contract: $registered",
            );
        }
    }

    public function testContractAndImplementationAreIdentical(): void
    {
        // Strongest form: the two sets are exactly equal (no drift in either direction).
        self::assertSame($this->registeredRoutes(), $this->documentedRoutes());
    }
}
