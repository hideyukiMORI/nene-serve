<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Marketplace\Billing\HandoffBillingPeriodHandler;
use NeneServe\Marketplace\Billing\HandoffBillingPeriodInput;
use NeneServe\Marketplace\Billing\HandoffBillingPeriodOutput;
use NeneServe\Marketplace\Billing\HandoffBillingPeriodUseCaseInterface;
use NeneServe\Marketplace\InvoiceHandoff;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class HandoffBillingPeriodHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testHandsOffPeriod(): void
    {
        $response = $this->handle();

        self::assertSame(200, $response->getStatusCode());
        /** @var array{status: string, invoice_payment_id: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('handed_off', $body['status']);
        self::assertSame('ipay-1', $body['invoice_payment_id']);
    }

    public function testRejectsAnonymousRequest(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new HandoffBillingPeriodHandler($this->useCase(), new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/admin/billing-periods/bp-1/handoff')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'bp-1']);

        self::assertSame(401, $handler->handle($request)->getStatusCode());
    }

    private function handle(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new HandoffBillingPeriodHandler($this->useCase(), new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/admin/billing-periods/bp-1/handoff')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'bp-1'])
            ->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);

        return $handler->handle($request);
    }

    private function useCase(): HandoffBillingPeriodUseCaseInterface
    {
        return new class () implements HandoffBillingPeriodUseCaseInterface {
            public function execute(HandoffBillingPeriodInput $input): HandoffBillingPeriodOutput
            {
                return new HandoffBillingPeriodOutput(new InvoiceHandoff(
                    'ho-1',
                    'org-acme',
                    $input->periodId,
                    'ho:org:bp-1:v1',
                    1000,
                    50,
                    1,
                    250_000,
                    'reconciled',
                    'handed_off',
                    'ipay-1',
                    '2026-06-05T00:00:00+00:00',
                ));
            }
        };
    }
}
