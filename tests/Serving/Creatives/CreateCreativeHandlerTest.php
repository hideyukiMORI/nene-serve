<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneServe\Serving\Creative;
use NeneServe\Serving\Creatives\CreateCreativeHandler;
use NeneServe\Serving\Creatives\CreateCreativeUseCaseInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\AuthContext;
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
            new ProblemDetailsResponseFactory($psr17, $psr17),
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
            public function createImage(AuthContext $actor, string $destinationUrl, string $assetUrl, int $width, int $height, ?string $campaignId = null): Creative
            {
                return new Creative('cr-1', $actor->organizationId, CreativeType::Image, ReviewStatus::Draft, $destinationUrl, $assetUrl, $width, $height);
            }

            public function createVideo(AuthContext $actor, string $destinationUrl, string $assetUrl, string $posterUrl, int $width, int $height, int $durationSeconds, ?string $campaignId = null): Creative
            {
                throw new \LogicException('not used');
            }

            public function createHtml5(AuthContext $actor, string $destinationUrl, string $bundleId, int $bundleSizeBytes, int $assetCount, string $htmlEntry, ?int $width = null, ?int $height = null, ?string $campaignId = null): Creative
            {
                throw new \LogicException('not used');
            }
        };
    }
}
