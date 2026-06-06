<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\Review\ImageAcceptance;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\UseCase\CreativeNotFoundException;
use NeneServe\Serving\UseCase\InvalidReviewTransitionException;
use NeneServe\Support\SqlDialect;

/**
 * Revises an approved creative. An approved version is immutable, so this never
 * edits in place: it creates a **new version** at `draft` (requiring re-review),
 * retaining the prior version as immutable history (creative-review §0.3/§1) —
 * committed atomically via the NENE2 transaction pattern.
 */
final readonly class ReviseCreativeUseCase implements ReviseCreativeUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
        private RequestScopedHolder $organizationId,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function execute(ReviseCreativeInput $input): ReviseCreativeOutput
    {
        $current = (new PdoCreativeRepository($this->query))->findByIdInOrganization($input->creativeId, $this->organizationId->get());

        if ($current === null) {
            throw new CreativeNotFoundException();
        }

        if ($current->reviewStatus !== ReviewStatus::Approved) {
            throw new InvalidReviewTransitionException('Only an approved creative is revised into a new version.');
        }

        if ($current->type === CreativeType::Image) {
            ImageAcceptance::assertValid($input->assetUrl, $input->destinationUrl, $input->width, $input->height);
        }

        $newVersion = $current->version + 1;
        $revision = new Creative(
            $current->id . '.v' . $newVersion,
            $current->organizationId,
            $current->type,
            ReviewStatus::Draft,
            $input->destinationUrl,
            $input->assetUrl,
            $input->width,
            $input->height,
            $newVersion,
        );

        $dialect = $this->dialect;
        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($revision, $current, $input, $dialect): Creative {
                (new PdoCreativeRepository($tx, $dialect))->save($revision); // prior version row is left untouched
                (new PdoAuditLog($tx))->record(
                    $revision->organizationId,
                    $input->actorUserId,
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

        return new ReviseCreativeOutput($stored);
    }
}
