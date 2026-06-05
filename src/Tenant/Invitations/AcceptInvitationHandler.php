<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Invitations;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/invitations/accept (operationId `acceptInvitation`).
 * UNAUTHENTICATED (like login): the invitee sets their password with the
 * single-use token.
 */
final readonly class AcceptInvitationHandler
{
    public function __construct(
        private AcceptInvitationUseCaseInterface $accept,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $token = isset($body['token']) && is_string($body['token']) ? $body['token'] : '';
        $password = isset($body['password']) && is_string($body['password']) ? $body['password'] : '';

        $errors = [];

        if ($token === '') {
            $errors[] = new ValidationError('token', 'Token is required.', 'required');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'Password is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->accept->execute($token, $password);

        return $this->response->create(['accepted' => true]);
    }
}
