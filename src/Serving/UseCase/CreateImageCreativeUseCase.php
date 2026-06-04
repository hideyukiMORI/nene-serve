<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\Review\ImageAcceptance;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Creates an image creative in `draft` after enforcing image acceptance rules
 * (ADR 0021 §3). It is not servable until it walks the review state machine to
 * `approved` (ADR 0020).
 */
final class CreateImageCreativeUseCase
{
    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(
        AuthContext $actor,
        string $destinationUrl,
        string $assetUrl,
        int $width,
        int $height,
    ): Creative {
        ImageAcceptance::assertValid($assetUrl, $destinationUrl, $width, $height);

        $creative = new Creative(
            'cr-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            CreativeType::Image,
            ReviewStatus::Draft,
            $destinationUrl,
            $assetUrl,
            $width,
            $height,
        );

        return $this->tx->transactional(function () use ($creative, $actor): Creative {
            $this->creatives->save($creative);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'creative.created',
                'creative',
                $creative->id,
                ['after' => ['type' => 'image', 'review_status' => 'draft']],
            );

            return $creative;
        });
    }
}
