<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Creatives;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\Creative;
use NeneServe\Serving\Creatives\GetCreativeByIdInput;
use NeneServe\Serving\Creatives\GetCreativeByIdOutput;
use NeneServe\Serving\Creatives\GetCreativeByIdUseCaseInterface;
use NeneServe\Serving\Creatives\GetCreativeHandler;
use NeneServe\Serving\Creatives\ListCreativesHandler;
use NeneServe\Serving\Creatives\ListCreativesInput;
use NeneServe\Serving\Creatives\ListCreativesOutput;
use NeneServe\Serving\Creatives\ListCreativesUseCaseInterface;
use NeneServe\Serving\Creatives\ReviewQueueHandler;
use NeneServe\Serving\Creatives\ReviewQueueInput;
use NeneServe\Serving\Creatives\ReviewQueueOutput;
use NeneServe\Serving\Creatives\ReviewQueueUseCaseInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\UseCase\CreativeNotFoundException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class CreativesReadHandlerTest extends TestCase
{
    public function testListReturnsPaginatedEnvelope(): void
    {
        $psr17 = new Psr17Factory();
        $useCase = new class () implements ListCreativesUseCaseInterface {
            public function execute(ListCreativesInput $input): ListCreativesOutput
            {
                return new ListCreativesOutput([
                    new Creative('cr-1', 'org-acme', CreativeType::Image, ReviewStatus::Approved, 'https://acme.test/a'),
                    new Creative('cr-2', 'org-acme', CreativeType::Image, ReviewStatus::Submitted, 'https://acme.test/b'),
                ], $input->limit, $input->offset);
            }
        };
        $handler = new ListCreativesHandler($useCase, $this->json($psr17));

        $response = $handler->handle($this->request($psr17, 'GET', '/admin/creatives'));

        self::assertSame(200, $response->getStatusCode());
        /** @var array{items: list<mixed>} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertCount(2, $body['items']);
    }

    public function testGetThrowsWhenMissing(): void
    {
        $psr17 = new Psr17Factory();
        $useCase = new class () implements GetCreativeByIdUseCaseInterface {
            public function execute(GetCreativeByIdInput $input): GetCreativeByIdOutput
            {
                throw new CreativeNotFoundException();
            }
        };
        $handler = new GetCreativeHandler($useCase, $this->json($psr17));

        $this->expectException(CreativeNotFoundException::class);
        $handler->handle($this->request($psr17, 'GET', '/admin/creatives/x')->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'x']));
    }

    public function testReviewQueueReturnsPaginatedEnvelope(): void
    {
        $psr17 = new Psr17Factory();
        $useCase = new class () implements ReviewQueueUseCaseInterface {
            public function execute(ReviewQueueInput $input): ReviewQueueOutput
            {
                return new ReviewQueueOutput([
                    new Creative('cr-2', 'org-acme', CreativeType::Image, ReviewStatus::Submitted, 'https://acme.test/b'),
                    new Creative('cr-3', 'org-acme', CreativeType::Html5Bundle, ReviewStatus::InReview, 'https://acme.test/c'),
                ], $input->limit, $input->offset);
            }
        };
        $handler = new ReviewQueueHandler($useCase, $this->json($psr17));

        $response = $handler->handle($this->request($psr17, 'GET', '/admin/review-queue'));

        self::assertSame(200, $response->getStatusCode());
        /** @var array{items: list<mixed>} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertCount(2, $body['items']);
    }

    private function request(Psr17Factory $psr17, string $method, string $path): ServerRequestInterface
    {
        return $psr17->createServerRequest($method, $path);
    }

    private function json(Psr17Factory $psr17): JsonResponseFactory
    {
        return new JsonResponseFactory($psr17, $psr17);
    }
}
