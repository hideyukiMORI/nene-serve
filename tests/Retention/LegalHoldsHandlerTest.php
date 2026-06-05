<?php

declare(strict_types=1);

namespace NeneServe\Tests\Retention;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneServe\Retention\LegalHold;
use NeneServe\Retention\LegalHolds\LegalHoldUseCaseInterface;
use NeneServe\Retention\LegalHolds\PlaceLegalHoldHandler;
use NeneServe\Retention\LegalHolds\PlaceLegalHoldInput;
use NeneServe\Retention\LegalHolds\PlaceLegalHoldOutput;
use NeneServe\Retention\LegalHolds\ReleaseLegalHoldInput;
use NeneServe\Retention\LegalHolds\ReleaseLegalHoldOutput;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class LegalHoldsHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testPlaceCreatesHold(): void
    {
        $response = $this->place('{"reason":"litigation pending"}');

        self::assertSame(201, $response->getStatusCode());
        /** @var array{reason: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('litigation pending', $body['reason']);
    }

    public function testPlaceRejectsMissingReason(): void
    {
        $this->expectException(ValidationException::class);
        $this->place('{}');
    }

    private function place(string $json): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new PlaceLegalHoldHandler($this->useCase(), new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/admin/legal-holds')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json))
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);

        return $handler->handle($request);
    }

    private function useCase(): LegalHoldUseCaseInterface
    {
        return new class () implements LegalHoldUseCaseInterface {
            public function place(PlaceLegalHoldInput $input): PlaceLegalHoldOutput
            {
                return new PlaceLegalHoldOutput(new LegalHold('lh-1', 'org-acme', $input->reason, gmdate('c')));
            }

            public function release(ReleaseLegalHoldInput $input): ReleaseLegalHoldOutput
            {
                throw new \LogicException('not used');
            }
        };
    }
}
