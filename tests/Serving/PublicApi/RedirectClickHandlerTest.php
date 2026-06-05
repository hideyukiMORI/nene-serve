<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\PublicApi;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\PublicApi\RecordClickUseCaseInterface;
use NeneServe\Serving\PublicApi\RedirectClickHandler;
use NeneServe\Serving\Token\ClickRedirect;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class RedirectClickHandlerTest extends TestCase
{
    public function testRedirectsToRegisteredDestination(): void
    {
        $redirect = new ClickRedirect('org-1', 'pl-1', 'cr-1', 'https://advertiser.example/landing');
        $response = $this->handle($this->useCase($redirect));

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://advertiser.example/landing', $response->getHeaderLine('Location'));
    }

    public function testInvalidTokenIs404(): void
    {
        self::assertSame(404, $this->handle($this->useCase(null))->getStatusCode());
    }

    public function testUnsafeDestinationIs422(): void
    {
        $redirect = new ClickRedirect('org-1', 'pl-1', 'cr-1', 'javascript:alert(1)');
        self::assertSame(422, $this->handle($this->useCase($redirect))->getStatusCode());
    }

    private function handle(RecordClickUseCaseInterface $useCase): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new RedirectClickHandler($useCase, new ProblemDetailsResponseFactory($psr17, $psr17), $psr17);

        $request = $psr17->createServerRequest('GET', '/public/clicks/tok')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['click_token' => 'tok']);

        return $handler->handle($request);
    }

    private function useCase(?ClickRedirect $redirect): RecordClickUseCaseInterface
    {
        return new class ($redirect) implements RecordClickUseCaseInterface {
            public function __construct(private readonly ?ClickRedirect $redirect)
            {
            }

            public function execute(string $token, ?string $countryCode = null): ?ClickRedirect
            {
                return $this->redirect;
            }
        };
    }
}
