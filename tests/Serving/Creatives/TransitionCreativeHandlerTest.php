<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Creatives;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\Creative;
use NeneServe\Serving\Creatives\TransitionCreativeHandler;
use NeneServe\Serving\Creatives\TransitionCreativeInput;
use NeneServe\Serving\Creatives\TransitionCreativeOutput;
use NeneServe\Serving\Creatives\TransitionCreativeUseCaseInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\Review\ReviewAction;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use NeneServe\Tenant\Auth\AuthContextRequiredException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class TransitionCreativeHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testApproveReturnsUpdatedCreative(): void
    {
        $useCase = new class () implements TransitionCreativeUseCaseInterface {
            public function execute(TransitionCreativeInput $input): TransitionCreativeOutput
            {
                return new TransitionCreativeOutput(new Creative($input->creativeId, 'org-acme', CreativeType::Image, ReviewStatus::Approved, 'https://acme.test/a'));
            }
        };

        $response = $this->handle($useCase, ReviewAction::Approve, self::CLAIMS);

        self::assertSame(200, $response->getStatusCode());
        /** @var array{review_status: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('approved', $body['review_status']);
    }

    public function testInvalidTransitionPropagatesToHandler(): void
    {
        $useCase = new class () implements TransitionCreativeUseCaseInterface {
            public function execute(TransitionCreativeInput $input): TransitionCreativeOutput
            {
                throw new InvalidReviewTransitionException('Cannot approve a creative in draft.');
            }
        };

        $this->expectException(InvalidReviewTransitionException::class);
        $this->handle($useCase, ReviewAction::Approve, self::CLAIMS);
    }

    public function testRejectsRequestWithoutClaims(): void
    {
        $useCase = new class () implements TransitionCreativeUseCaseInterface {
            public function execute(TransitionCreativeInput $input): TransitionCreativeOutput
            {
                throw new \LogicException('Use case must not be reached.');
            }
        };

        $this->expectException(AuthContextRequiredException::class);
        $this->handle($useCase, ReviewAction::Approve, null);
    }

    /**
     * @param array<string, mixed>|null $claims
     */
    private function handle(TransitionCreativeUseCaseInterface $useCase, ReviewAction $action, ?array $claims): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $handler = new TransitionCreativeHandler(
            $action,
            $useCase,
            new JsonResponseFactory($psr17, $psr17),
        );

        $request = $psr17->createServerRequest('POST', '/admin/creatives/cr-1/approve')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'cr-1']);

        if ($claims !== null) {
            $request = $request->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, $claims);
        }

        return $handler->handle($request);
    }
}
