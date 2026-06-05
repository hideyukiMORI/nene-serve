<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/login (operationId `login`). Unauthenticated entry point that
 * exchanges organization + email + password for an admin bearer token.
 */
final readonly class LoginHandler
{
    public function __construct(
        private LoginUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $organization = isset($body['organization']) && is_string($body['organization']) ? trim($body['organization']) : '';
        $email = isset($body['email']) && is_string($body['email']) ? trim($body['email']) : '';
        $password = isset($body['password']) && is_string($body['password']) ? $body['password'] : '';

        $errors = [];

        if ($organization === '') {
            $errors[] = new ValidationError('organization', 'Organization is required.', 'required');
        }

        if ($email === '') {
            $errors[] = new ValidationError('email', 'Email is required.', 'required');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'Password is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new LoginInput($organization, $email, $password));

        return $this->response->create([
            'token' => $output->token,
            'user' => $output->user,
        ]);
    }
}
