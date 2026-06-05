<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
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
        private UpdateSmtpSettingsUseCaseInterface $update,
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
        $rawPassword = isset($body['password']) && is_string($body['password']) ? $body['password'] : null;

        $record = $this->update->execute(new UpdateSmtpSettingsInput(
            $context->userId,
            $host,
            $port,
            $username,
            $rawPassword,
            $fromAddress,
            $fromName,
            $encryption,
        ))->record;

        return $this->response->create($record->toAdminArray() + ['configured' => true]);
    }
}
