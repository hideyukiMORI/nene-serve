<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Tenant\UseCase\AcceptInvitationUseCase;

/**
 * GET /admin/invitations/{token} (operationId `getInvitation`). UNAUTHENTICATED.
 * Lets the set-password page confirm a token is valid (and show the email)
 * before rendering the form. Reveals only the email for a valid token.
 */
final class PreviewInvitationHandler
{
    public function __construct(
        private readonly AcceptInvitationUseCase $accept,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        $user = $this->accept->preview((string) $request->param('token'));
        if ($user === null) {
            return $this->json->problem(404, 'invitation-invalid', 'Invitation is invalid, used, or expired');
        }

        return $this->json->ok(['email' => $user->email]);
    }
}
