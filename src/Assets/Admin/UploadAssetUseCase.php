<?php

declare(strict_types=1);

namespace NeneServe\Assets\Admin;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Assets\Asset;
use NeneServe\Assets\PdoAssetRepository;
use NeneServe\Assets\UseCase\AssetValidationException;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Storage\StorageInterface;
use NeneServe\Support\Id;

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

    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private StorageInterface $storage,
        private RequestScopedHolder $organizationId,
    ) {
    }

    public function execute(UploadAssetInput $input): UploadAssetOutput
    {
        $kind = self::ALLOWED[$input->contentType] ?? null;

        if ($kind === null) {
            throw new AssetValidationException('Unsupported content type: ' . $input->contentType);
        }

        if ($input->bytes === '') {
            throw new AssetValidationException('Empty upload.');
        }

        if (strlen($input->bytes) > self::MAX_BYTES) {
            throw new AssetValidationException('Upload exceeds the maximum size.');
        }

        $asset = new Asset(
            Id::generate('ast', 12),
            $this->organizationId->get(),
            $kind,
            $input->contentType,
            strlen($input->bytes),
        );

        // Bytes first (outside the txn), then commit metadata + audit atomically.
        $this->storage->put($asset->id, $input->bytes);

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($asset, $input): Asset {
                (new PdoAssetRepository($tx))->save($asset);
                (new PdoAuditLog($tx))->record(
                    $asset->organizationId,
                    $input->actorUserId,
                    'asset.uploaded',
                    'asset',
                    $asset->id,
                    ['kind' => $asset->kind, 'content_type' => $asset->contentType, 'byte_size' => $asset->byteSize],
                );

                return $asset;
            },
        );

        return new UploadAssetOutput($stored);
    }
}
