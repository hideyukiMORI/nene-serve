<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement\Dsr;

use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\InMemoryAuditLog;
use NeneServe\Measurement\Dsr\DataSubjectRequestUseCase;
use NeneServe\Measurement\Dsr\EraseVisitorDataInput;
use NeneServe\Measurement\Dsr\ExportVisitorDataInput;
use NeneServe\Measurement\ImpressionEvent;
use NeneServe\Measurement\InMemoryEventStore;
use PHPUnit\Framework\TestCase;

/**
 * Data-subject access/erasure (privacy §5). The load-bearing invariant: erasure
 * is an additive tombstone — it forgets the visitor LINK (export returns nothing
 * afterwards) but never edits the impression COUNT (billing substantiation is
 * retained). Both operations are tenant-scoped and audited.
 */
final class DataSubjectRequestUseCaseTest extends TestCase
{
    private const ORG = 'org-1';
    private const BUCKET = 'bucket-abc';

    private InMemoryEventStore $events;
    private InMemoryAuditLog $audit;

    protected function setUp(): void
    {
        $this->events = new InMemoryEventStore();
        $this->audit = new InMemoryAuditLog();
    }

    private function useCase(string $org = self::ORG): DataSubjectRequestUseCase
    {
        /** @var RequestScopedHolder<string> $holder */
        $holder = new RequestScopedHolder();
        $holder->set($org);

        return new DataSubjectRequestUseCase($this->events, $this->audit, $holder);
    }

    private function seedImpression(string $org, string $bucket): void
    {
        $this->events->recordImpression(new ImpressionEvent(
            'imp-1',
            $org,
            'plc-1',
            'cr-1',
            '2026-06-06T00:00:00+00:00',
            null,
            null,
            $bucket,
        ));
    }

    private function impressionCount(): int
    {
        $total = 0;
        foreach ($this->events->dailyMetrics(self::ORG, '2000-01-01', '2100-01-01') as $row) {
            $total += $row->impressions;
        }

        return $total;
    }

    public function testExportReturnsTheVisitorRecordsAndAudits(): void
    {
        $this->seedImpression(self::ORG, self::BUCKET);

        $output = $this->useCase()->export(new ExportVisitorDataInput('admin-1', self::BUCKET));

        self::assertCount(1, $output->records);

        $events = $this->audit->allForOrganization(self::ORG);
        self::assertCount(1, $events);
        self::assertSame('dsr.export', $events[0]->action);
        self::assertSame(1, $events[0]->metadata['record_count']);
    }

    public function testErasureForgetsTheLinkButKeepsTheCount(): void
    {
        $this->seedImpression(self::ORG, self::BUCKET);
        $useCase = $this->useCase();

        $erase = $useCase->erase(new EraseVisitorDataInput('admin-1', self::BUCKET));

        self::assertSame(1, $erase->tombstoned);
        // The visitor link is forgotten...
        self::assertCount(0, $useCase->export(new ExportVisitorDataInput('admin-1', self::BUCKET))->records);
        // ...but the impression count is retained (billing carve-out).
        self::assertSame(1, $this->impressionCount());
    }

    public function testErasureAuditsTheTombstoneCount(): void
    {
        $this->seedImpression(self::ORG, self::BUCKET);

        $this->useCase()->erase(new EraseVisitorDataInput('admin-1', self::BUCKET));

        $events = $this->audit->allForOrganization(self::ORG);
        $erasure = array_values(array_filter($events, static fn ($e) => $e->action === 'dsr.erasure'));
        self::assertCount(1, $erasure);
        self::assertSame(1, $erasure[0]->metadata['tombstoned_count']);
    }

    public function testIsTenantScoped(): void
    {
        // Impression belongs to another tenant; org-1's request must not see it.
        $this->seedImpression('org-2', self::BUCKET);

        $output = $this->useCase(self::ORG)->export(new ExportVisitorDataInput('admin-1', self::BUCKET));

        self::assertCount(0, $output->records);
    }

    public function testExportOfAnUnknownBucketIsEmptyButStillAudited(): void
    {
        $output = $this->useCase()->export(new ExportVisitorDataInput('admin-1', 'no-such-bucket'));

        self::assertCount(0, $output->records);
        self::assertCount(1, $this->audit->allForOrganization(self::ORG));
    }
}
