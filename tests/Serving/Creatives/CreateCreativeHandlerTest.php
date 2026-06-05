<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Creatives;

use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneServe\Serving\Creative;
use NeneServe\Serving\Creatives\CreateCreativeHandler;
use NeneServe\Serving\Creatives\CreateCreativeOutput;
use NeneServe\Serving\Creatives\CreateCreativeUseCaseInterface;
use NeneServe\Serving\Creatives\CreateHtml5CreativeInput;
use NeneServe\Serving\Creatives\CreateImageCreativeInput;
use NeneServe\Serving\Creatives\CreateVideoCreativeInput;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class CreateCreativeHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testCreatesImageCreative(): void
    {
        $response = $this->handle('{"type":"image","destination_url":"https://acme.test/l","asset_url":"https://acme.test/a.png","width":300,"height":250}');

        self::assertSame(201, $response->getStatusCode());
        /** @var array{type: string, review_status: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('image', $body['type']);
        self::assertSame('draft', $body['review_status']);
    }

    public function testRejectsMissingFields(): void
    {
        $this->expectException(ValidationException::class);
        $this->handle('{"type":"image"}');
    }

    public function testRejectsUnsupportedType(): void
    {
        $this->expectException(ValidationException::class);
        $this->handle('{"type":"third_party_tag"}');
    }

    private function handle(string $json): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new CreateCreativeHandler(
            $this->useCase(),
            new JsonResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('POST', '/admin/creatives')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json))
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);

        return $handler->handle($request);
    }

    private function useCase(): CreateCreativeUseCaseInterface
    {
        return new class () implements CreateCreativeUseCaseInterface {
            public function createImage(CreateImageCreativeInput $input): CreateCreativeOutput
            {
                return new CreateCreativeOutput(new Creative('cr-1', 'org-acme', CreativeType::Image, ReviewStatus::Draft, $input->destinationUrl, $input->assetUrl, $input->width, $input->height));
            }

            public function createVideo(CreateVideoCreativeInput $input): CreateCreativeOutput
            {
                throw new \LogicException('not used');
            }

            public function createHtml5(CreateHtml5CreativeInput $input): CreateCreativeOutput
            {
                throw new \LogicException('not used');
            }
        };
    }
}
