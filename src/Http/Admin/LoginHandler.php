<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Tenant\UseCase\AuthenticationFailedException;
use NeneServe\Tenant\UseCase\LoginUseCase;

/**
 * POST /admin/login (operationId `login`). Unauthenticated entry point that
 * exchanges org + email + password for an admin JWT.
 */
final class LoginHandler
{
    public function __construct(
        private readonly LoginUseCase $login,
        private readonly JsonResponseFactory $json,
    ) {
    }

    public function handle(Request $request): Response
    {
        $body = $request->json();
        $organization = $body['organization'] ?? null;
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (!is_string($organization) || !is_string($email) || !is_string($password)
            || $organization === '' || $email === '' || $password === '') {
            return $this->json->problem(
                422,
                'validation-failed',
                'Invalid login request',
                'Fields organization, email, and password are required.',
            );
        }

        try {
            $result = $this->login->execute($organization, $email, $password);
        } catch (AuthenticationFailedException) {
            return $this->json->problem(401, 'unauthorized', 'Authentication failed');
        }

        return $this->json->ok($result);
    }
}
