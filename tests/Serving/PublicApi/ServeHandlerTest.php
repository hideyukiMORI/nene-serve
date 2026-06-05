<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\PublicApi;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\PublicApi\ServeCreativeUseCaseInterface;
use NeneServe\Serving\PublicApi\ServeHandler;
use NeneServe\Serving\UseCase\NoEligibleCreativeException;
use NeneServe\Serving\UseCase\PlacementNotFoundException;
use NeneServe\Serving\UseCase\ServeResult;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ServeHandlerTest extends TestCase
{
    public function testServesPayloadWithCors(): void
    {
        $result = new ServeResult(['creative' => ['id' => 'c1'], 'click_url' => '/public/clicks/t'], 'https://pub.example');
        $response = $this->handle($this->useCase($result), 'https://pub.example');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('https://pub.example', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testEmptyServeIs204(): void
    {
        $useCase = new class () implements ServeCreativeUseCaseInterface {
            public function execute(string $publicPlacementKey, ?string $origin, bool $consentGranted = false, string $clientIp = '', string $userAgent = ''): ServeResult
            {
                throw new NoEligibleCreativeException();
            }
        };

        $response = $this->handle($useCase, null);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testUnknownPlacementIs404(): void
    {
        $useCase = new class () implements ServeCreativeUseCaseInterface {
            public function execute(string $publicPlacementKey, ?string $origin, bool $consentGranted = false, string $clientIp = '', string $userAgent = ''): ServeResult
            {
                throw new PlacementNotFoundException();
            }
        };

        self::assertSame(404, $this->handle($useCase, null)->getStatusCode());
    }

    private function handle(ServeCreativeUseCaseInterface $useCase, ?string $origin): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new ServeHandler($useCase, new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17), $psr17);

        $request = $psr17->createServerRequest('GET', '/public/placements/pk/serve')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['public_placement_key' => 'pk']);

        if ($origin !== null) {
            $request = $request->withHeader('Origin', $origin);
        }

        return $handler->handle($request);
    }

    private function useCase(ServeResult $result): ServeCreativeUseCaseInterface
    {
        return new class ($result) implements ServeCreativeUseCaseInterface {
            public function __construct(private readonly ServeResult $result)
            {
            }

            public function execute(string $publicPlacementKey, ?string $origin, bool $consentGranted = false, string $clientIp = '', string $userAgent = ''): ServeResult
            {
                return $this->result;
            }
        };
    }
}
