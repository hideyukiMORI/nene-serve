<?php

declare(strict_types=1);

namespace NeneServe\Auth;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Resolution\OrgResolverMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/login (operationId `login`). Unauthenticated entry point that
 * exchanges organization + email + password for an admin bearer token.
 *
 * In a URL resolution mode (ADR 0006) the tenant has been resolved from the
 * request by {@see OrgResolverMiddleware} and is authoritative: the body's
 * `organization` is ignored, so a tenant URL only ever signs into its own org.
 * In `login` mode no tenant is resolved and the body `organization` is required.
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

        // A tenant resolved from the URL wins over the body (URL modes lock login
        // to their own org); login mode falls back to the submitted slug.
        $resolvedSlug = $request->getAttribute(OrgResolverMiddleware::RESOLVED_ORG_SLUG_ATTRIBUTE);
        $organization = is_string($resolvedSlug) && $resolvedSlug !== ''
            ? $resolvedSlug
            : (isset($body['organization']) && is_string($body['organization']) ? trim($body['organization']) : '');
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
