<?php

declare(strict_types=1);

namespace NeneServe\Assets\UseCase;

use NeneServe\Assets\Asset;
use NeneServe\Assets\AssetRepositoryInterface;
use NeneServe\Audit\AuditLogInterface;
use NeneServe\Storage\StorageInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Validate and store an uploaded image/video. Only an allowlisted content type
 * is accepted (no SVG — script vector; no arbitrary types). Bytes go to object
 * storage under the opaque asset id; governed metadata is persisted + audited.
 */
final class UploadAssetUseCase
{
    public const MAX_BYTES = 8 * 1024 * 1024;

    /** @var array<string, 'image'|'video'> */
    private const ALLOWED = [
        'image/png' => 'image',
        'image/jpeg' => 'image',
        'image/gif' => 'image',
        'image/webp' => 'image',
        'video/mp4' => 'video',
        'video/webm' => 'video',
    ];

    public function __construct(
        private readonly AssetRepositoryInterface $assets,
        private readonly StorageInterface $storage,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(AuthContext $actor, string $contentType, string $bytes): Asset
    {
        $kind = self::ALLOWED[$contentType] ?? null;
        if ($kind === null) {
            throw new AssetValidationException('Unsupported content type: ' . $contentType);
        }
        if ($bytes === '') {
            throw new AssetValidationException('Empty upload.');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new AssetValidationException('Upload exceeds the maximum size.');
        }

        $asset = new Asset(
            'ast-' . bin2hex(random_bytes(12)),
            $actor->organizationId,
            $kind,
            $contentType,
            strlen($bytes),
        );

        // Bytes first (outside the txn), then commit metadata + audit atomically.
        $this->storage->put($asset->id, $bytes);

        return $this->tx->transactional(function () use ($asset, $actor): Asset {
            $this->assets->save($asset);
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'asset.uploaded',
                'asset',
                $asset->id,
                ['kind' => $asset->kind, 'content_type' => $asset->contentType, 'byte_size' => $asset->byteSize],
            );

            return $asset;
        });
    }
}
