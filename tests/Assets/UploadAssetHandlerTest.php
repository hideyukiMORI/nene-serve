<?php

declare(strict_types=1);

namespace NeneServe\Tests\Assets;

use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneServe\Assets\Admin\UploadAssetHandler;
use NeneServe\Assets\Admin\UploadAssetInput;
use NeneServe\Assets\Admin\UploadAssetOutput;
use NeneServe\Assets\Admin\UploadAssetUseCaseInterface;
use NeneServe\Assets\Asset;
use NeneServe\Assets\UseCase\AssetValidationException;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class UploadAssetHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testStoresUpload(): void
    {
        $response = $this->handle('{"content_type":"image/png","data_base64":"' . base64_encode('PNGDATA') . '"}');

        self::assertSame(201, $response->getStatusCode());
        /** @var array{kind: string, asset_url: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('image', $body['kind']);
        self::assertStringStartsWith('/public/assets/', $body['asset_url']);
    }

    public function testRejectsMissingFields(): void
    {
        $this->expectException(ValidationException::class);
        $this->handle('{"content_type":"image/png"}');
    }

    public function testRejectsInvalidBase64(): void
    {
        $this->expectException(ValidationException::class);
        $this->handle('{"content_type":"image/png","data_base64":"!!!not base64!!!"}');
    }

    private function handle(string $json): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new UploadAssetHandler($this->useCase(), new JsonResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/admin/assets')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json))
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);

        return $handler->handle($request);
    }

    private function useCase(): UploadAssetUseCaseInterface
    {
        return new class () implements UploadAssetUseCaseInterface {
            public function execute(UploadAssetInput $input): UploadAssetOutput
            {
                if ($input->contentType !== 'image/png') {
                    throw new AssetValidationException('Unsupported content type: ' . $input->contentType);
                }

                return new UploadAssetOutput(new Asset('ast-1', 'org-acme', 'image', $input->contentType, strlen($input->bytes)));
            }
        };
    }
}
