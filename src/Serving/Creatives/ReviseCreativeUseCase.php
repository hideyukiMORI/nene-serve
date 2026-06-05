<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\Review\ImageAcceptance;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Tenant\AuthContext;

/**
 * Revises an approved creative. An approved version is immutable, so this never
 * edits in place: it creates a **new version** at `draft` (requiring re-review),
 * retaining the prior version as immutable history (creative-review §0.3/§1) —
 * committed atomically via the NENE2 transaction pattern.
 */
final readonly class ReviseCreativeUseCase implements ReviseCreativeUseCaseInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
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
        $current = (new PdoCreativeRepository($this->query))->findByIdInOrganization($creativeId, $actor->organizationId);

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

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($revision, $current, $actor): Creative {
                (new PdoCreativeRepository($tx))->save($revision); // prior version row is left untouched
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'creative.version_superseded',
                    'creative',
                    $current->id,
                    [
                        'before' => ['version' => $current->version],
                        'after' => ['version' => $revision->version, 'new_creative_id' => $revision->id, 'review_status' => 'draft'],
                    ],
                );

                return $revision;
            },
        );
    }
}
