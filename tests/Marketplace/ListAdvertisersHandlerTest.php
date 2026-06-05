<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use Nene2\Http\JsonResponseFactory;
use NeneServe\Marketplace\Admin\ListAdvertisersHandler;
use NeneServe\Marketplace\Admin\ListAdvertisersInput;
use NeneServe\Marketplace\Admin\ListAdvertisersOutput;
use NeneServe\Marketplace\Admin\ListAdvertisersUseCaseInterface;
use NeneServe\Marketplace\Advertiser;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ListAdvertisersHandlerTest extends TestCase
{
    public function testListsAdvertisersInPaginatedEnvelope(): void
    {
        $response = $this->handle();

        self::assertSame(200, $response->getStatusCode());
        /** @var array{items: list<array{name: string}>, limit: int, offset: int} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Acme Media', $body['items'][0]['name']);
        self::assertSame(20, $body['limit']);
        self::assertSame(0, $body['offset']);
    }

    private function handle(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $useCase = new class () implements ListAdvertisersUseCaseInterface {
            public function execute(ListAdvertisersInput $input): ListAdvertisersOutput
            {
                return new ListAdvertisersOutput([new Advertiser('adv-1', 'org-acme', 'Acme Media', 'active')], $input->limit, $input->offset);
            }
        };
        $handler = new ListAdvertisersHandler($useCase, new JsonResponseFactory($psr17, $psr17));

        return $handler->handle($psr17->createServerRequest('GET', '/admin/advertisers'));
    }
}
