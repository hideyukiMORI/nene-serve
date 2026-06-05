<?php

declare(strict_types=1);

namespace NeneServe\Settings;

use Nene2\Http\RequestScopedHolder;

final readonly class GetSmtpSettingsUseCase implements GetSmtpSettingsUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private SmtpSettingsRepositoryInterface $settings,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(): GetSmtpSettingsOutput
    {
        return new GetSmtpSettingsOutput($this->settings->find($this->organizationId->get()));
    }
}
