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
use NeneServe\Serving\Review\Html5Acceptance;
use NeneServe\Serving\Review\ImageAcceptance;
use NeneServe\Serving\Review\VideoAcceptance;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\Scan\BundleScannerInterface;
use NeneServe\Support\Id;

/**
 * Creates a creative in `draft` after enforcing the per-type acceptance rules
 * (ADR 0021 §3); HTML5 bundles are malware-scanned (ADR 0021 §4). Not servable
 * until it walks the review state machine to `approved` (ADR 0020). Each create
 * commits its row + audit entry together (NENE2 transaction pattern).
 */
final readonly class CreateCreativeUseCase implements CreateCreativeUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private BundleScannerInterface $scanner,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function createImage(CreateImageCreativeInput $input): CreateCreativeOutput
    {
        ImageAcceptance::assertValid($input->assetUrl, $input->destinationUrl, $input->width, $input->height);

        $creative = new Creative(
            Id::generate('cr'),
            $this->organizationId->get(),
            CreativeType::Image,
            ReviewStatus::Draft,
            $input->destinationUrl,
            $input->assetUrl,
            $input->width,
            $input->height,
            campaignId: $input->campaignId,
        );

        return $this->persist($creative, $input->actorUserId, ['type' => 'image', 'review_status' => 'draft'], 'creative.created');
    }

    public function createVideo(CreateVideoCreativeInput $input): CreateCreativeOutput
    {
        VideoAcceptance::assertValid($input->assetUrl, $input->posterUrl, $input->destinationUrl, $input->durationSeconds);

        $creative = new Creative(
            Id::generate('cr'),
            $this->organizationId->get(),
            CreativeType::Video,
            ReviewStatus::Draft,
            $input->destinationUrl,
            $input->assetUrl,
            $input->width,
            $input->height,
            1,
            null,
            null,
            $input->posterUrl,
            $input->durationSeconds,
            campaignId: $input->campaignId,
        );

        return $this->persist($creative, $input->actorUserId, ['type' => 'video', 'review_status' => 'draft'], 'creative.created');
    }

    public function createHtml5(CreateHtml5CreativeInput $input): CreateCreativeOutput
    {
        Html5Acceptance::assertValid($input->bundleSizeBytes, $input->assetCount, $input->destinationUrl, $input->htmlEntry);

        $scanStatus = $this->scanner->scan($input->bundleId, $input->htmlEntry);

        $creative = new Creative(
            Id::generate('cr'),
            $this->organizationId->get(),
            CreativeType::Html5Bundle,
            ReviewStatus::Draft,
            $input->destinationUrl,
            null,
            $input->width,
            $input->height,
            1,
            null,
            null,
            null,
            null,
            $input->bundleId,
            $input->bundleSizeBytes,
            $scanStatus,
            $input->campaignId,
        );

        return $this->persist(
            $creative,
            $input->actorUserId,
            ['type' => 'html5_bundle', 'review_status' => 'draft', 'scan_status' => $scanStatus->value],
            'creative.scanned',
        );
    }

    /**
     * @param array<string, mixed> $after
     */
    private function persist(Creative $creative, string $actorUserId, array $after, string $action): CreateCreativeOutput
    {
        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($creative, $actorUserId, $after, $action): Creative {
                (new PdoCreativeRepository($tx))->save($creative);
                (new PdoAuditLog($tx))->record(
                    $creative->organizationId,
                    $actorUserId,
                    $action,
                    'creative',
                    $creative->id,
                    ['after' => $after],
                );

                return $creative;
            },
        );

        return new CreateCreativeOutput($stored);
    }
}
