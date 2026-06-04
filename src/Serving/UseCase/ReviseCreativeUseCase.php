<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\Review\ImageAcceptance;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Tenant\AuthContext;

/**
 * Revises an approved creative. An approved version is immutable, so this never
 * edits in place: it creates a **new version** at `draft` (requiring re-review),
 * retaining the prior version as immutable history (creative-review §0.3/§1).
 */
final class ReviseCreativeUseCase
{
    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly AuditLogInterface $audit,
    ) {
    }

    public function execute(
        AuthContext $actor,
        string $creativeId,
        string $destinationUrl,
        string $assetUrl,
        int $width,
        int $height,
    ): Creative {
        $current = $this->creatives->findByIdInOrganization($creativeId, $actor->organizationId);
        if ($current === null) {
            throw new CreativeNotFoundException();
        }
        if ($current->reviewStatus !== ReviewStatus::Approved) {
            throw new InvalidReviewTransitionException('Only an approved creative is revised into a new version.');
        }
        if ($current->type === CreativeType::Image) {
            ImageAcceptance::assertValid($assetUrl, $destinationUrl, $width, $height);
        }

        $newVersion = $current->version + 1;
        $revision = new Creative(
            $current->id . '.v' . $newVersion,
            $current->organizationId,
            $current->type,
            ReviewStatus::Draft,
            $destinationUrl,
            $assetUrl,
            $width,
            $height,
            $newVersion,
        );
        $this->creatives->save($revision); // prior version row is left untouched

        $this->audit->record(
            $actor->organizationId,
            $actor->userId,
            'creative.version_superseded',
            'creative',
            $current->id,
            ['from_version' => $current->version, 'new_creative_id' => $revision->id],
        );

        return $revision;
    }
}
