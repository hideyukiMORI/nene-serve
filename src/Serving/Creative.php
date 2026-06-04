<?php

declare(strict_types=1);

namespace NeneServe\Serving;

/**
 * A reviewable creative. Only an `approved` creative is ever served (ADR 0020);
 * `destinationUrl` is registered here and is the only redirect target the click
 * endpoint will use (no open redirect — ADR 0019/0021).
 *
 * Instances are immutable; review transitions and revisions return new
 * instances (an approved version is frozen — creative-review §0.3). `posterUrl`
 * and `durationSeconds` apply to video creatives (ADR 0021 §3).
 */
final class Creative
{
    public function __construct(
        public readonly string $id,
        public readonly string $organizationId,
        public readonly CreativeType $type,
        public readonly ReviewStatus $reviewStatus,
        public readonly string $destinationUrl,
        public readonly ?string $assetUrl = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly int $version = 1,
        public readonly ?string $submittedBy = null,
        public readonly ?string $reviewReason = null,
        public readonly ?string $posterUrl = null,
        public readonly ?int $durationSeconds = null,
        public readonly ?string $bundleId = null,
        public readonly ?int $bundleSizeBytes = null,
        public readonly ?ScanStatus $scanStatus = null,
    ) {
    }

    public function isServable(): bool
    {
        return $this->reviewStatus->isServable();
    }

    /** Editable only in `draft` / `changes_requested` (creative-review §1). */
    public function isEditable(): bool
    {
        return $this->reviewStatus->isEditable();
    }

    public function withReview(ReviewStatus $status, ?string $submittedBy = null, ?string $reason = null): self
    {
        return new self(
            $this->id,
            $this->organizationId,
            $this->type,
            $status,
            $this->destinationUrl,
            $this->assetUrl,
            $this->width,
            $this->height,
            $this->version,
            $submittedBy ?? $this->submittedBy,
            $reason,
            $this->posterUrl,
            $this->durationSeconds,
            $this->bundleId,
            $this->bundleSizeBytes,
            $this->scanStatus,
        );
    }

    /** HTML5 bundle is servable only when its malware scan is clean (ADR 0021 §4). */
    public function isScanClean(): bool
    {
        return $this->scanStatus === ScanStatus::Clean;
    }

    /**
     * Public render payload — no internal ids, org data, or review metadata
     * (least exposure, api-security §2). Video carries a poster and an explicit
     * `autoplay:false` (no autoplay-with-sound by default — ADR 0021 §3).
     *
     * @return array<string, mixed>
     */
    public function toServePayload(): array
    {
        $payload = [
            'type' => $this->type->value,
            'asset_url' => $this->assetUrl,
            'width' => $this->width,
            'height' => $this->height,
        ];

        if ($this->type === CreativeType::Video) {
            $payload['poster_url'] = $this->posterUrl;
            $payload['autoplay'] = false;
        }

        // HTML5 `render` (sandbox + frame URL) is added by ServeCreativeUseCase,
        // which holds the token store needed for the opaque frame URL.

        return array_filter($payload, static fn ($v) => $v !== null);
    }

    /**
     * Admin projection — includes review metadata, never visible on the public surface.
     *
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'review_status' => $this->reviewStatus->value,
            'destination_url' => $this->destinationUrl,
            'asset_url' => $this->assetUrl,
            'width' => $this->width,
            'height' => $this->height,
            'poster_url' => $this->posterUrl,
            'duration_seconds' => $this->durationSeconds,
            'bundle_id' => $this->bundleId,
            'bundle_size_bytes' => $this->bundleSizeBytes,
            'scan_status' => $this->scanStatus?->value,
            'version' => $this->version,
            'review_reason' => $this->reviewReason,
        ];
    }
}
