<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Tenant\UseCase\AcceptInvitationUseCase;
use NeneServe\Tenant\UseCase\InvitationInvalidException;
use NeneServe\Tenant\UseCase\UserValidationException;

/**
 * POST /admin/invitations/accept (operationId `acceptInvitation`). UNAUTHENTICATED
 * (like login): the invitee sets their password with the single-use token.
 */
final class AcceptInvitationHandler
{
    public function __construct(
        private readonly AcceptInvitationUseCase $accept,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        $body = $request->json();
        $token = $body['token'] ?? null;
        $password = $body['password'] ?? null;
        if (!is_string($token) || !is_string($password)) {
            return $this->json->problem(422, 'validation-failed', 'token and password are required');
        }

        try {
            $this->accept->execute($token, $password);
        } catch (UserValidationException $e) {
            return $this->json->problem(422, 'weak-password', 'Password does not meet policy', $e->getMessage());
        } catch (InvitationInvalidException) {
            return $this->json->problem(404, 'invitation-invalid', 'Invitation is invalid, used, or expired');
        }

        return $this->json->ok(['accepted' => true]);
    }
}
