<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/invitations/{token} (operationId `getInvitation`). UNAUTHENTICATED.
 * Lets the set-password page confirm a token is valid (and show the email)
 * before rendering the form. Reveals only the email for a valid token.
 */
final readonly class PreviewInvitationHandler
{
    public function __construct(
        private AcceptInvitationUseCaseInterface $accept,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = Router::param($request, 'token') ?? '';

        $user = $this->accept->preview(new PreviewInvitationInput($token))->user;

        if ($user === null) {
            return $this->problemDetails->create($request, 'invitation-invalid', 'Invitation invalid', 404, 'Invitation is invalid, used, or expired.');
        }

        return $this->response->create(['email' => $user->email]);
    }
}
