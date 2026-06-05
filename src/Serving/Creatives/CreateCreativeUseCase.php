<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\Review\Html5Acceptance;
use NeneServe\Serving\Review\ImageAcceptance;
use NeneServe\Serving\Review\VideoAcceptance;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\Scan\BundleScannerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Creates a creative in `draft` after enforcing the per-type acceptance rules
 * (ADR 0021 §3); HTML5 bundles are malware-scanned (ADR 0021 §4). Not servable
 * until it walks the review state machine to `approved` (ADR 0020). Each create
 * commits its row + audit entry together (NENE2 transaction pattern).
 */
final readonly class CreateCreativeUseCase implements CreateCreativeUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private BundleScannerInterface $scanner,
    ) {
    }

    public function createImage(AuthContext $actor, string $destinationUrl, string $assetUrl, int $width, int $height, ?string $campaignId = null): Creative
    {
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
            campaignId: $campaignId,
        );

        return $this->persist($creative, $actor, ['type' => 'image', 'review_status' => 'draft'], 'creative.created');
    }

    public function createVideo(AuthContext $actor, string $destinationUrl, string $assetUrl, string $posterUrl, int $width, int $height, int $durationSeconds, ?string $campaignId = null): Creative
    {
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
            campaignId: $campaignId,
        );

        return $this->persist($creative, $actor, ['type' => 'video', 'review_status' => 'draft'], 'creative.created');
    }

    public function createHtml5(AuthContext $actor, string $destinationUrl, string $bundleId, int $bundleSizeBytes, int $assetCount, string $htmlEntry, ?int $width = null, ?int $height = null, ?string $campaignId = null): Creative
    {
        Html5Acceptance::assertValid($bundleSizeBytes, $assetCount, $destinationUrl, $htmlEntry);

        $scanStatus = $this->scanner->scan($bundleId, $htmlEntry);

        $creative = new Creative(
            'cr-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            CreativeType::Html5Bundle,
            ReviewStatus::Draft,
            $destinationUrl,
            null,
            $width,
            $height,
            1,
            null,
            null,
            null,
            null,
            $bundleId,
            $bundleSizeBytes,
            $scanStatus,
            $campaignId,
        );

        return $this->persist(
            $creative,
            $actor,
            ['type' => 'html5_bundle', 'review_status' => 'draft', 'scan_status' => $scanStatus->value],
            'creative.scanned',
        );
    }

    /**
     * @param array<string, mixed> $after
     */
    private function persist(Creative $creative, AuthContext $actor, array $after, string $action): Creative
    {
        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($creative, $actor, $after, $action): Creative {
                (new PdoCreativeRepository($tx))->save($creative);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    $action,
                    'creative',
                    $creative->id,
                    ['after' => $after],
                );

                return $creative;
            },
        );
    }
}
