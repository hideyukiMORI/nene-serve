<?php

declare(strict_types=1);

namespace NeneServe\Http\Admin;

use NeneServe\Http\JsonResponseFactory;
use NeneServe\Http\Request;
use NeneServe\Http\Response;
use NeneServe\Mail\Email;
use NeneServe\Mail\MailerException;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Settings\SmtpConfigResolver;
use NeneServe\Support\CryptoException;
use NeneServe\Tenant\AuthContext;
use NeneServe\Tenant\UseCase\CreateInvitedUserUseCase;
use NeneServe\Tenant\UseCase\UserValidationException;

/**
 * POST /admin/users (operationId `createUser`). Requires `manage_users`. Creates
 * an invited account and emails a single-use set-password link (best-effort: the
 * account is created even if mail is unconfigured/fails — `invite_email_sent`
 * reports it). The raw token is never returned by the API.
 */
final class CreateUserHandler
{
    public function __construct(
        private readonly CreateInvitedUserUseCase $createUser,
        private readonly SmtpConfigResolver $smtp,
        private readonly MailerFactoryInterface $mailerFactory,
        private readonly JsonResponseFactory $json,
        private readonly string $appBaseUrl,
    ) {
    }

    public function handle(Request $request, AuthContext $context): Response
    {
        $body = $request->json();
        $email = $body['email'] ?? null;
        $role = $body['role'] ?? null;
        if (!is_string($email) || !is_string($role)) {
            return $this->json->problem(422, 'validation-failed', 'email and role are required');
        }

        try {
            $invited = $this->createUser->execute($context, $email, $role);
        } catch (UserValidationException $e) {
            return $this->json->problem(422, 'user-invalid', 'Invalid user', $e->getMessage());
        }

        $emailSent = $this->sendInvite($context->organizationId, $invited->user->email, $invited->rawToken);

        return $this->json->ok(
            $invited->user->toPublicArray() + ['invite_email_sent' => $emailSent],
            201,
        );
    }

    private function sendInvite(string $organizationId, string $recipient, string $rawToken): bool
    {
        try {
            $config = $this->smtp->resolve($organizationId);
        } catch (CryptoException) {
            return false;
        }
        if ($config === null) {
            return false;
        }

        $link = rtrim($this->appBaseUrl, '/') . '/set-password?token=' . $rawToken;
        try {
            $this->mailerFactory->fromConfig($config)->send(new Email(
                $recipient,
                'NeNe Serve — set your password',
                "You've been invited to NeNe Serve.\n\nSet your password (link valid for 72 hours):\n" . $link . "\n",
            ));
        } catch (MailerException) {
            return false;
        }

        return true;
    }
}
