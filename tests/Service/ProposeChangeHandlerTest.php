<?php

declare(strict_types=1);

namespace NeneServe\Tests\Service;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneServe\Mcp\Api\ProposePlacementChangeUseCaseInterface;
use NeneServe\Mcp\ChangePlan;
use NeneServe\Service\Api\ProposeChangeHandler;
use NeneServe\Service\Auth\ServiceAuthMiddleware;
use NeneServe\Service\Scope;
use NeneServe\Service\ServiceContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ProposeChangeHandlerTest extends TestCase
{
    public function testProposesChange(): void
    {
        $response = $this->handle('{"placement_id":"pl-1","new_creative_id":"cr-1"}', $this->context());

        self::assertSame(201, $response->getStatusCode());
        /** @var array{status: string, confirmation_token: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('proposed', $body['status']);
        self::assertSame('plan-1', $body['confirmation_token']);
    }

    public function testRejectsAnonymousRequest(): void
    {
        self::assertSame(401, $this->handle('{"placement_id":"pl-1","new_creative_id":"cr-1"}', null)->getStatusCode());
    }

    public function testRejectsMissingFields(): void
    {
        $this->expectException(ValidationException::class);
        $this->handle('{"placement_id":"pl-1"}', $this->context());
    }

    private function context(): ServiceContext
    {
        return new ServiceContext('org-acme', [Scope::WriteDeliveryPlan]);
    }

    private function handle(string $json, ?ServiceContext $context): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new ProposeChangeHandler($this->useCase(), new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/api/delivery-plan-changes')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream($json));

        if ($context !== null) {
            $request = $request->withAttribute(ServiceAuthMiddleware::CONTEXT_ATTRIBUTE, $context);
        }

        return $handler->handle($request);
    }

    private function useCase(): ProposePlacementChangeUseCaseInterface
    {
        return new class () implements ProposePlacementChangeUseCaseInterface {
            public function execute(ServiceContext $context, string $placementId, string $newCreativeId): ChangePlan
            {
                return new ChangePlan('plan-1', $context->organizationId, $placementId, $newCreativeId, 'proposed', '2026-06-05T00:00:00+00:00');
            }
        };
    }
}
