<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Http\ParsesRequiredBodyFields;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /admin/creatives (operationId `createCreative`). Requires
 * `manage_creatives`. Supports image / video / html5_bundle (ADR 0021 §3); all
 * start in `draft`. `third_party_tag` is forbidden.
 */
final readonly class CreateCreativeHandler
{
    use ParsesRequiredBodyFields;

    public function __construct(
        private CreateCreativeUseCaseInterface $createCreative,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::require($request);

        $body = JsonRequestBodyParser::parse($request);
        $type = isset($body['type']) && is_string($body['type']) ? $body['type'] : 'image';
        $campaignId = isset($body['campaign_id']) && is_string($body['campaign_id']) ? $body['campaign_id'] : null;

        $output = match ($type) {
            'image' => $this->createCreative->createImage(new CreateImageCreativeInput(
                $context->userId,
                $this->str($body, 'destination_url'),
                $this->str($body, 'asset_url'),
                $this->int($body, 'width'),
                $this->int($body, 'height'),
                $campaignId,
            )),
            'video' => $this->createCreative->createVideo(new CreateVideoCreativeInput(
                $context->userId,
                $this->str($body, 'destination_url'),
                $this->str($body, 'asset_url'),
                $this->str($body, 'poster_url'),
                $this->int($body, 'width'),
                $this->int($body, 'height'),
                $this->int($body, 'duration_seconds'),
                $campaignId,
            )),
            'html5_bundle' => $this->createCreative->createHtml5(new CreateHtml5CreativeInput(
                $context->userId,
                $this->str($body, 'destination_url'),
                $this->str($body, 'bundle_id'),
                $this->int($body, 'bundle_size_bytes'),
                $this->int($body, 'asset_count'),
                $this->str($body, 'html_entry'),
                isset($body['width']) && is_int($body['width']) ? $body['width'] : null,
                isset($body['height']) && is_int($body['height']) ? $body['height'] : null,
                $campaignId,
            )),
            default => throw new ValidationException([
                new ValidationError('type', 'Unsupported creative type; third_party_tag is forbidden.', 'invalid'),
            ]),
        };

        return $this->response->create($output->creative->toAdminArray(), 201);
    }

    /** @param array<string, mixed> $body */
    /** @param array<string, mixed> $body */
}
