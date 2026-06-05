<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Support\Crypto;
use NeneServe\Support\CryptoException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PUT /admin/settings/smtp (operationId `updateSmtpSettings`). Requires
 * `manage_settings`. The password is encrypted at rest (Support\Crypto); an
 * omitted/empty password keeps the stored one. Audited (`settings.smtp_updated`).
 */
final readonly class UpdateSmtpSettingsHandler
{
    private const ENCRYPTIONS = ['none', 'starttls', 'tls'];

    public function __construct(
        private SmtpSettingsRepositoryInterface $settings,
        private Crypto $crypto,
        private AuditLogInterface $audit,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        $body = JsonRequestBodyParser::parse($request);

        $host = isset($body['host']) && is_string($body['host']) ? trim($body['host']) : '';
        $port = $body['port'] ?? null;
        $fromAddress = isset($body['from_address']) && is_string($body['from_address']) ? trim($body['from_address']) : '';
        $encryption = isset($body['encryption']) && is_string($body['encryption']) ? $body['encryption'] : 'starttls';

        $errors = [];

        if ($host === '') {
            $errors[] = new ValidationError('host', 'Host is required.', 'required');
        }

        if (!is_int($port)) {
            $errors[] = new ValidationError('port', 'Port must be an integer.', 'invalid');
        }

        if ($fromAddress === '') {
            $errors[] = new ValidationError('from_address', 'From address is required.', 'required');
        }

        if (!in_array($encryption, self::ENCRYPTIONS, true)) {
            $errors[] = new ValidationError('encryption', 'Encryption must be none, starttls or tls.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var int $port */
        /** @var 'none'|'starttls'|'tls' $encryption */
        $username = isset($body['username']) && is_string($body['username']) ? $body['username'] : '';
        $fromName = isset($body['from_name']) && is_string($body['from_name']) ? $body['from_name'] : '';

        $existing = $this->settings->find($context->organizationId);
        $passwordEncrypted = $existing?->passwordEncrypted;
        $newPassword = $body['password'] ?? null;

        if (is_string($newPassword) && $newPassword !== '') {
            try {
                $passwordEncrypted = $this->crypto->encrypt($newPassword);
            } catch (CryptoException) {
                return $this->problemDetails->create($request, 'encryption-unavailable', 'Encryption unavailable', 500, 'At-rest encryption is not configured.');
            }
        }

        $record = new SmtpSettingsRecord(
            $context->organizationId,
            $host,
            $port,
            $username,
            $passwordEncrypted,
            $fromAddress,
            $fromName,
            $encryption,
        );
        $this->settings->save($record);
        $this->audit->record(
            $context->organizationId,
            $context->userId,
            'settings.smtp_updated',
            'smtp_settings',
            $context->organizationId,
            ['host' => $host, 'encryption' => $encryption],
        );

        return $this->response->create($record->toAdminArray() + ['configured' => true]);
    }
}
