<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneServe\Marketplace\Admin\CreateAdvertiserHandler;
use NeneServe\Marketplace\Admin\CreateAdvertiserInput;
use NeneServe\Marketplace\Admin\CreateAdvertiserOutput;
use NeneServe\Marketplace\Admin\CreateAdvertiserUseCaseInterface;
use NeneServe\Marketplace\Advertiser;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class CreateAdvertiserHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testCreatesAdvertiser(): void
    {
        $response = $this->handle('{"name":"Globex"}');

        self::assertSame(201, $response->getStatusCode());
        /** @var array{name: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Globex', $body['name']);
    }

    public function testRejectsMissingName(): void
    {
        $this->expectException(ValidationException::class);
        $this->handle('{}');
    }

    private function handle(string $json): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new CreateAdvertiserHandler(
            $this->useCase(),
            new JsonResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('POST', '/admin/advertisers')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json))
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);

        return $handler->handle($request);
    }

    private function useCase(): CreateAdvertiserUseCaseInterface
    {
        return new class () implements CreateAdvertiserUseCaseInterface {
            public function execute(CreateAdvertiserInput $input): CreateAdvertiserOutput
            {
                return new CreateAdvertiserOutput(new Advertiser('adv-1', 'org-acme', $input->name, 'active', $input->invoiceClientId));
            }
        };
    }
}
