<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Serving\Creative;
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
    public function __construct(
        private CreateCreativeUseCaseInterface $createCreative,
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
        $type = isset($body['type']) && is_string($body['type']) ? $body['type'] : 'image';
        $campaignId = isset($body['campaign_id']) && is_string($body['campaign_id']) ? $body['campaign_id'] : null;

        $creative = match ($type) {
            'image' => $this->createCreative->createImage(
                $context,
                $this->str($body, 'destination_url'),
                $this->str($body, 'asset_url'),
                $this->int($body, 'width'),
                $this->int($body, 'height'),
                $campaignId,
            ),
            'video' => $this->createCreative->createVideo(
                $context,
                $this->str($body, 'destination_url'),
                $this->str($body, 'asset_url'),
                $this->str($body, 'poster_url'),
                $this->int($body, 'width'),
                $this->int($body, 'height'),
                $this->int($body, 'duration_seconds'),
                $campaignId,
            ),
            'html5_bundle' => $this->createCreative->createHtml5(
                $context,
                $this->str($body, 'destination_url'),
                $this->str($body, 'bundle_id'),
                $this->int($body, 'bundle_size_bytes'),
                $this->int($body, 'asset_count'),
                $this->str($body, 'html_entry'),
                isset($body['width']) && is_int($body['width']) ? $body['width'] : null,
                isset($body['height']) && is_int($body['height']) ? $body['height'] : null,
                $campaignId,
            ),
            default => throw new ValidationException([
                new ValidationError('type', 'Unsupported creative type; third_party_tag is forbidden.', 'invalid'),
            ]),
        };

        return $this->respond($creative);
    }

    private function respond(Creative $creative): ResponseInterface
    {
        return $this->response->create($creative->toAdminArray(), 201);
    }

    /** @param array<string, mixed> $body */
    private function str(array $body, string $key): string
    {
        if (!isset($body[$key]) || !is_string($body[$key]) || $body[$key] === '') {
            throw new ValidationException([new ValidationError($key, sprintf('%s is required.', $key), 'required')]);
        }

        return $body[$key];
    }

    /** @param array<string, mixed> $body */
    private function int(array $body, string $key): int
    {
        if (!isset($body[$key]) || !is_int($body[$key])) {
            throw new ValidationException([new ValidationError($key, sprintf('%s must be an integer.', $key), 'invalid')]);
        }

        return $body[$key];
    }
}
