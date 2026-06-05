<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use NeneServe\Http\BodyFieldCollector;
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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $body = JsonRequestBodyParser::parse($request);

        $fields = new BodyFieldCollector($body);
        $host = $fields->requiredString('host', 'Host is required.', trim: true);
        $port = $fields->requiredInt('port', 'Port must be an integer.');
        $fromAddress = $fields->requiredString('from_address', 'From address is required.', trim: true);
        $encryption = $fields->oneOf('encryption', self::ENCRYPTIONS, 'Encryption must be none, starttls or tls.', 'starttls');
        $fields->throwIfInvalid();

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
