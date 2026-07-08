<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement;

use NeneServe\Measurement\VisitorBucket;
use PHPUnit\Framework\TestCase;

/**
 * Hashed rotating visitor bucket (privacy P4/N3). It must be deterministic for a
 * fixed (org, day, ip, ua) and decorrelated across every input dimension so
 * buckets cannot be joined across orgs or days.
 */
final class VisitorBucketTest extends TestCase
{
    public function testIsDeterministicForTheSameInputs(): void
    {
        $a = VisitorBucket::derive('org-1', '1.2.3.4', 'UA/1', '2026-06-06');
        $b = VisitorBucket::derive('org-1', '1.2.3.4', 'UA/1', '2026-06-06');
        self::assertSame($a, $b);
    }

    public function testIsA32CharHexString(): void
    {
        $bucket = VisitorBucket::derive('org-1', '1.2.3.4', 'UA/1', '2026-06-06');
        self::assertSame(32, strlen($bucket));
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $bucket);
    }

    public function testDiffersAcrossOrganizations(): void
    {
        self::assertNotSame(
            VisitorBucket::derive('org-1', '1.2.3.4', 'UA/1', '2026-06-06'),
            VisitorBucket::derive('org-2', '1.2.3.4', 'UA/1', '2026-06-06'),
        );
    }

    public function testDiffersAcrossDays(): void
    {
        self::assertNotSame(
            VisitorBucket::derive('org-1', '1.2.3.4', 'UA/1', '2026-06-06'),
            VisitorBucket::derive('org-1', '1.2.3.4', 'UA/1', '2026-06-07'),
        );
    }

    public function testDiffersAcrossClientIp(): void
    {
        self::assertNotSame(
            VisitorBucket::derive('org-1', '1.2.3.4', 'UA/1', '2026-06-06'),
            VisitorBucket::derive('org-1', '9.9.9.9', 'UA/1', '2026-06-06'),
        );
    }

    public function testDiffersAcrossUserAgent(): void
    {
        self::assertNotSame(
            VisitorBucket::derive('org-1', '1.2.3.4', 'UA/1', '2026-06-06'),
            VisitorBucket::derive('org-1', '1.2.3.4', 'UA/2', '2026-06-06'),
        );
    }

    public function testFieldSeparatorPreventsCollisionFromConcatenation(): void
    {
        // Without a separator, ('a','b') and ('ab','') would collide.
        self::assertNotSame(
            VisitorBucket::derive('a', 'b', 'UA', '2026-06-06'),
            VisitorBucket::derive('ab', '', 'UA', '2026-06-06'),
        );
    }
}
