<?php

declare(strict_types=1);

namespace NeneServe\Tests\Tenant\Invitations;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Invitations\AcceptInvitationHandler;
use NeneServe\Tenant\Invitations\AcceptInvitationInput;
use NeneServe\Tenant\Invitations\AcceptInvitationOutput;
use NeneServe\Tenant\Invitations\AcceptInvitationUseCaseInterface;
use NeneServe\Tenant\Invitations\PreviewInvitationHandler;
use NeneServe\Tenant\Invitations\PreviewInvitationInput;
use NeneServe\Tenant\Invitations\PreviewInvitationOutput;
use NeneServe\Tenant\Role;
use NeneServe\Tenant\User;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class InvitationsHandlerTest extends TestCase
{
    public function testPreviewReturnsEmailForValidToken(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new PreviewInvitationHandler($this->useCase(found: true), new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('GET', '/admin/invitations/tok')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['token' => 'tok']);

        $response = $handler->handle($request);
        self::assertSame(200, $response->getStatusCode());
        /** @var array{email: string} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('invitee@acme.test', $body['email']);
    }

    public function testPreviewReturns404ForInvalidToken(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new PreviewInvitationHandler($this->useCase(found: false), new JsonResponseFactory($psr17, $psr17), new ProblemDetailsResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('GET', '/admin/invitations/tok')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['token' => 'tok']);

        self::assertSame(404, $handler->handle($request)->getStatusCode());
    }

    public function testAcceptRejectsMissingFields(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new AcceptInvitationHandler($this->useCase(found: true), new JsonResponseFactory($psr17, $psr17));

        $request = $psr17->createServerRequest('POST', '/admin/invitations/accept')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($psr17->createStream('{}'));

        $this->expectException(ValidationException::class);
        $handler->handle($request);
    }

    private function useCase(bool $found): AcceptInvitationUseCaseInterface
    {
        $user = $found ? new User('u-1', 'org-acme', 'invitee@acme.test', Role::Editor, '') : null;

        return new class ($user) implements AcceptInvitationUseCaseInterface {
            public function __construct(private readonly ?User $user)
            {
            }

            public function execute(AcceptInvitationInput $input): AcceptInvitationOutput
            {
                return new AcceptInvitationOutput($this->user ?? throw new \LogicException('not used'));
            }

            public function preview(PreviewInvitationInput $input): PreviewInvitationOutput
            {
                return new PreviewInvitationOutput($this->user);
            }
        };
    }
}
