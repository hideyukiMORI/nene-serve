<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeneServe\Assets\Asset;
use NeneServe\Assets\PdoAssetRepository;
use NeneServe\Assets\UseCase\AssetValidationException;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Storage\StorageInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Validate and store an uploaded image/video. Only an allowlisted content type
 * is accepted (no SVG — script vector; no arbitrary types). Bytes go to object
 * storage under the opaque asset id; governed metadata is persisted + audited.
 */
final readonly class UploadAssetUseCase implements UploadAssetUseCaseInterface
{
    public const int MAX_BYTES = 8 * 1024 * 1024;

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
        private DatabaseTransactionManagerInterface $transactions,
        private StorageInterface $storage,
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

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($asset, $actor): Asset {
                (new PdoAssetRepository($tx))->save($asset);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'asset.uploaded',
                    'asset',
                    $asset->id,
                    ['kind' => $asset->kind, 'content_type' => $asset->contentType, 'byte_size' => $asset->byteSize],
                );

                return $asset;
            },
        );
    }
}
