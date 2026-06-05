<?php

declare(strict_types=1);

namespace NeneServe\Serving;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoCreativeRepository implements CreativeRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, type, review_status, destination_url, asset_url, width, height, version, submitted_by, review_reason, poster_url, duration_seconds, bundle_id, bundle_size_bytes, scan_status, campaign_id';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Creative
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM creatives WHERE id = ? AND organization_id = ? LIMIT 1',
            [$id, $organizationId],
        );

        return $row === null ? null : $this->hydrate($row);
    }

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM creatives WHERE organization_id = ? ORDER BY id LIMIT ? OFFSET ?',
            [$organizationId, $limit, $offset],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function listReviewQueue(string $organizationId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . " FROM creatives WHERE organization_id = ? AND review_status IN ('submitted', 'in_review') ORDER BY id LIMIT ? OFFSET ?",
            [$organizationId, $limit, $offset],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function save(Creative $creative): void
    {
        $this->query->execute(
            'INSERT INTO creatives (id, organization_id, type, review_status, destination_url, asset_url, width, height, version, submitted_by, review_reason, poster_url, duration_seconds, bundle_id, bundle_size_bytes, scan_status, campaign_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE type = new.type, review_status = new.review_status, destination_url = new.destination_url, asset_url = new.asset_url, width = new.width, height = new.height, version = new.version, submitted_by = new.submitted_by, review_reason = new.review_reason, poster_url = new.poster_url, duration_seconds = new.duration_seconds, bundle_id = new.bundle_id, bundle_size_bytes = new.bundle_size_bytes, scan_status = new.scan_status, campaign_id = new.campaign_id',
            [
                $creative->id,
                $creative->organizationId,
                $creative->type->value,
                $creative->reviewStatus->value,
                $creative->destinationUrl,
                $creative->assetUrl,
                $creative->width,
                $creative->height,
                $creative->version,
                $creative->submittedBy,
                $creative->reviewReason,
                $creative->posterUrl,
                $creative->durationSeconds,
                $creative->bundleId,
                $creative->bundleSizeBytes,
                $creative->scanStatus?->value,
                $creative->campaignId,
            ],
        );
    }

    public function idsByCampaign(string $organizationId, string $campaignId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id FROM creatives WHERE organization_id = ? AND campaign_id = ?',
            [$organizationId, $campaignId],
        );

        return array_map(static fn (array $row): string => (string) $row['id'], $rows);
    }

    public function idsWithCampaign(string $organizationId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id FROM creatives WHERE organization_id = ? AND campaign_id IS NOT NULL',
            [$organizationId],
        );

        return array_map(static fn (array $row): string => (string) $row['id'], $rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Creative
    {
        return new Creative(
            (string) $row['id'],
            (string) $row['organization_id'],
            CreativeType::from((string) $row['type']),
            ReviewStatus::from((string) $row['review_status']),
            (string) $row['destination_url'],
            $row['asset_url'] !== null ? (string) $row['asset_url'] : null,
            $row['width'] !== null ? (int) $row['width'] : null,
            $row['height'] !== null ? (int) $row['height'] : null,
            (int) $row['version'],
            $row['submitted_by'] !== null ? (string) $row['submitted_by'] : null,
            $row['review_reason'] !== null ? (string) $row['review_reason'] : null,
            $row['poster_url'] !== null ? (string) $row['poster_url'] : null,
            $row['duration_seconds'] !== null ? (int) $row['duration_seconds'] : null,
            $row['bundle_id'] !== null ? (string) $row['bundle_id'] : null,
            $row['bundle_size_bytes'] !== null ? (int) $row['bundle_size_bytes'] : null,
            $row['scan_status'] !== null ? ScanStatus::from((string) $row['scan_status']) : null,
            $row['campaign_id'] !== null ? (string) $row['campaign_id'] : null,
        );
    }
}
