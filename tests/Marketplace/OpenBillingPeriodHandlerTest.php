<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationException;
use NeneServe\Marketplace\Billing\OpenBillingPeriodHandler;
use NeneServe\Marketplace\Billing\OpenBillingPeriodInput;
use NeneServe\Marketplace\Billing\OpenBillingPeriodOutput;
use NeneServe\Marketplace\Billing\OpenBillingPeriodUseCaseInterface;
use NeneServe\Marketplace\BillingPeriod;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class OpenBillingPeriodHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testOpensPeriod(): void
    {
        $response = $this->handle('{"period_start":"2026-06-01","period_end":"2026-06-30"}');

        self::assertSame(201, $response->getStatusCode());
        /** @var array{status: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('open', $body['status']);
    }

    public function testRejectsMissingDates(): void
    {
        $this->expectException(ValidationException::class);
        $this->handle('{}');
    }

    private function handle(string $json): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new OpenBillingPeriodHandler($this->useCase(), new JsonResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/admin/campaigns/cmp-1/billing-periods')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json))
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'cmp-1'])
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);

        return $handler->handle($request);
    }

    private function useCase(): OpenBillingPeriodUseCaseInterface
    {
        return new class () implements OpenBillingPeriodUseCaseInterface {
            public function execute(OpenBillingPeriodInput $input): OpenBillingPeriodOutput
            {
                return new OpenBillingPeriodOutput(new BillingPeriod('bp-1', 'org-acme', $input->campaignId, $input->periodStart, $input->periodEnd, 'open'));
            }
        };
    }
}
