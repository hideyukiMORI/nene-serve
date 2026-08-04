<?php

declare(strict_types=1);

namespace NeneServe\Serving;

use RuntimeException;

/**
 * Raised when the public serving surface is asked for storage it cannot serve
 * production from — an unrecognised `NENE_SERVE_PUBLIC_STORE`, or single-host
 * file storage in production.
 *
 * A boot failure by design. The alternative is a deployment that looks healthy
 * and drops a fraction of clicks once a second host joins.
 *
 * Sibling of {@see \NeneServe\Http\RateLimit\RateLimitStorageException}: kept
 * separate rather than shared because they name different surfaces, and nothing
 * catches either — both are meant to stop the boot.
 */
final class PublicStoreException extends RuntimeException
{
}
