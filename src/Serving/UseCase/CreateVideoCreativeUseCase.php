<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\Review\VideoAcceptance;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Tenant\AuthContext;

/**
 * Creates a video creative in `draft` after enforcing video acceptance rules
 * (ADR 0021 §3). Not servable until it reaches `approved` via the same review
 * state machine as image (#13).
 */
final class CreateVideoCreativeUseCase
{
    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly AuditLogInterface $audit,
    ) {
    }

    public function execute(
        AuthContext $actor,
        string $destinationUrl,
        string $assetUrl,
        string $posterUrl,
        int $width,
        int $height,
        int $durationSeconds,
    ): Creative {
        VideoAcceptance::assertValid($assetUrl, $posterUrl, $destinationUrl, $durationSeconds);

        $creative = new Creative(
            'cr-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            CreativeType::Video,
            ReviewStatus::Draft,
            $destinationUrl,
            $assetUrl,
            $width,
            $height,
            1,
            null,
            null,
            $posterUrl,
            $durationSeconds,
        );
        $this->creatives->save($creative);

        $this->audit->record(
            $actor->organizationId,
            $actor->userId,
            'creative.created',
            'creative',
            $creative->id,
            ['type' => 'video'],
        );

        return $creative;
    }
}
