<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use NeneServe\Serving\InMemoryPlacementRepository;
use NeneServe\Serving\Placement;
use PHPUnit\Framework\TestCase;

/**
 * "Delete" is an archive tombstone, not a row removal (ADR 0022 §3): the row is
 * retained, the placement stops being active, and there is no hard-delete method.
 */
final class PlacementArchiveTest extends TestCase
{
    public function testArchiveTombstonesInsteadOfDeleting(): void
    {
        $repo = new InMemoryPlacementRepository([
            new Placement('p1', 'org-1', 'pk', [], 'active', 'c1'),
        ]);

        $repo->archive('p1', 'org-1', '2026-06-04T00:00:00+00:00');

        $placement = $repo->findByIdInOrganization('p1', 'org-1');
        self::assertNotNull($placement, 'row is retained, not deleted');
        self::assertSame('archived', $placement->status);
        self::assertSame('2026-06-04T00:00:00+00:00', $placement->archivedAt);
        self::assertFalse($placement->isActive(), 'archived placement no longer serves');
    }
}
