<?php

declare(strict_types=1);

namespace NeneServe\Serving\Frequency;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Frequency caps counted from the impression events themselves, rather than from
 * a counter kept alongside them.
 *
 * This is what the file store's own docblock said production would do, and the
 * reason is worth stating: a parallel counter is a second number that can
 * disagree with the first. Impression counts are audit-grade (ADR 0015) and feed
 * billing; a cap counter that drifts would either over-serve a capped placement
 * or refuse to serve one that had room, with no way to tell which number was
 * right. Counting the events removes the question.
 *
 * It also removes a window: the file store incremented *after* the impression
 * row was written, so a crash in between left the counter behind. Here the row
 * is the count.
 *
 * The bucket rotates per UTC day ({@see \NeneServe\Serving\VisitorBucket}), so
 * "impressions to this visitor for this placement" is already a per-day window —
 * no expiry logic, and nothing to prune. Erased rows drop out because erasure
 * nulls the visitor link (privacy §5); `erased_at IS NULL` is stated anyway, to
 * match the other visitor-scoped queries.
 *
 * Migration 0036 adds the index this needs; the query runs on the serve path.
 */
final readonly class PdoFrequencyCapStore implements FrequencyCapStoreInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function count(string $placementId, string $visitorBucket): int
    {
        $row = $this->query->fetchOne(
            'SELECT COUNT(*) AS delivered FROM impressions
             WHERE placement_id = ? AND visitor_bucket = ? AND erased_at IS NULL',
            [$placementId, $visitorBucket],
        );

        return (int) ($row['delivered'] ?? 0);
    }

    /**
     * Intentionally does nothing.
     *
     * The caller increments right after recording the impression event, and that
     * event **is** the increment — {@see self::count()} reads it. Writing a
     * second number here would double count, which is the exact failure the
     * derived count exists to prevent.
     *
     * The method stays because the interface is shared with the file store,
     * where the counter is separate and the call is load-bearing.
     */
    public function increment(string $placementId, string $visitorBucket): void
    {
    }
}
