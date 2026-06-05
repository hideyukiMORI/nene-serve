<?php

declare(strict_types=1);

namespace NeneServe\Tests\Service;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeneServe\Service\Auth\ScopeMiddleware;
use NeneServe\Service\Auth\ServiceAuthMiddleware;
use NeneServe\Service\Scope;
use NeneServe\Service\ServiceContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ScopeMiddlewareTest extends TestCase
{
    public function testAllowsTokenWithRequiredScope(): void
    {
        $response = $this->process(new ServiceContext('org-1', [Scope::ReadPlacements]), '/api/placements');

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRefusesTokenWithoutRequiredScope(): void
    {
        $response = $this->process(new ServiceContext('org-1', [Scope::ReadMetrics]), '/api/placements');

        self::assertSame(403, $response->getStatusCode());
    }

    public function testPassesThroughWhenNoContext(): void
    {
        $response = $this->process(null, '/public/placements/pk/serve');

        self::assertSame(200, $response->getStatusCode());
    }

    private function process(?ServiceContext $context, string $path): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $middleware = new ScopeMiddleware(new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('GET', $path);

        if ($context !== null) {
            $request = $request->withAttribute(ServiceAuthMiddleware::CONTEXT_ATTRIBUTE, $context);
        }

        $next = new class ($psr17) implements RequestHandlerInterface {
            public function __construct(private readonly Psr17Factory $psr17)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->psr17->createResponse(200);
            }
        };

        return $middleware->process($request, $next);
    }
}
