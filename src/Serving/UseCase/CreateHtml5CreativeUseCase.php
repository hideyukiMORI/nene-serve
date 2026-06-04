<?php

declare(strict_types=1);

namespace NeneServe\Serving\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\Review\Html5Acceptance;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\Scan\BundleScannerInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Creates an HTML5 bundle creative in `draft` (ADR 0020/0021): enforces bundle
 * acceptance + content policy, then runs the malware scan. The scan result is
 * stored and audited; a `flagged` bundle is recorded but blocked from submission
 * (the submit guard enforces scan=clean). Not servable until `approved`.
 */
final class CreateHtml5CreativeUseCase
{
    public function __construct(
        private readonly CreativeRepositoryInterface $creatives,
        private readonly BundleScannerInterface $scanner,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(
        AuthContext $actor,
        string $destinationUrl,
        string $bundleId,
        int $bundleSizeBytes,
        int $assetCount,
        string $htmlEntry,
        ?int $width = null,
        ?int $height = null,
        ?string $campaignId = null,
    ): Creative {
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
        return $this->tx->transactional(function () use ($creative, $actor, $scanStatus): Creative {
            $this->creatives->save($creative);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'creative.scanned',
                'creative',
                $creative->id,
                ['after' => ['type' => 'html5_bundle', 'review_status' => 'draft', 'scan_status' => $scanStatus->value]],
            );

            return $creative;
        });
    }
}
