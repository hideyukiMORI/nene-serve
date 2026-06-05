<?php

declare(strict_types=1);

namespace NeneServe\Tenant\Users;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Http\BodyFieldCollector;
use NeneServe\Mail\Email;
use NeneServe\Mail\MailerException;
use NeneServe\Mail\MailerFactoryInterface;
use NeneServe\Settings\SmtpConfigResolver;
use NeneServe\Support\CryptoException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/users (operationId `createUser`). Requires `manage_users`. Creates
 * an invited account and emails a single-use set-password link (best-effort: the
 * account is created even if mail is unconfigured/fails — `invite_email_sent`
 * reports it). The raw token is never returned by the API.
 */
final readonly class CreateUserHandler
{
    public function __construct(
        private CreateInvitedUserUseCaseInterface $createUser,
        private SmtpConfigResolver $smtp,
        private MailerFactoryInterface $mailerFactory,
        private JsonResponseFactory $response,
        private string $appBaseUrl,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $body = JsonRequestBodyParser::parse($request);

        $fields = new BodyFieldCollector($body);
        $email = $fields->requiredString('email', 'Email is required.', trim: true);
        $role = $fields->requiredString('role', 'Role is required.');
        $fields->throwIfInvalid();

        $invited = $this->createUser->execute(new CreateInvitedUserInput($context->userId, $email, $role));

        $emailSent = $this->sendInvite($context->organizationId, $invited->user->email, $invited->rawToken);

        return $this->response->create(
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
