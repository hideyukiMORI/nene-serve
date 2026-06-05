<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\PdoCreativeRepository;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\ScanStatus;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Real SELECT/hydration of PdoCreativeRepository against in-memory SQLite: enum
 * casts (type / review_status / scan_status), nullable numeric fields, tenant
 * scoping, the review-queue status filter, and campaign id lookups.
 */
final class PdoCreativeRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $db;
    private PdoCreativeRepository $repo;

    protected function setUp(): void
    {
        $this->db = TestDatabase::withSchema('creatives');
        $this->repo = new PdoCreativeRepository($this->db);
    }

    /** @param array<string, scalar|null> $overrides */
    private function seedCreative(array $overrides = []): void
    {
        TestDatabase::seed($this->db, 'creatives', $overrides + [
            'id' => 'cr-1',
            'organization_id' => 'org-1',
            'type' => 'image',
            'review_status' => 'approved',
            'destination_url' => 'https://adv.example.com/l',
            'asset_url' => 'https://cdn.example.com/a.png',
            'width' => 300,
            'height' => 250,
            'version' => 1,
            'submitted_by' => 'u-1',
            'review_reason' => null,
            'poster_url' => null,
            'duration_seconds' => null,
            'bundle_id' => null,
            'bundle_size_bytes' => null,
            'scan_status' => null,
            'campaign_id' => null,
        ]);
    }

    public function testHydratesEnumsAndNumericFields(): void
    {
        $this->seedCreative([
            'type' => 'video',
            'review_status' => 'in_review',
            'scan_status' => 'clean',
            'duration_seconds' => 30,
            'poster_url' => 'https://cdn.example.com/p.jpg',
        ]);

        $creative = $this->repo->findByIdInOrganization('cr-1', 'org-1');

        self::assertNotNull($creative);
        self::assertSame(CreativeType::Video, $creative->type);
        self::assertSame(ReviewStatus::InReview, $creative->reviewStatus);
        self::assertSame(ScanStatus::Clean, $creative->scanStatus);
        self::assertSame(30, $creative->durationSeconds);
        self::assertSame(300, $creative->width);
    }

    public function testHydratesNullScanStatusAndOptionalFields(): void
    {
        $this->seedCreative(['asset_url' => null, 'width' => null, 'height' => null]);

        $creative = $this->repo->findByIdInOrganization('cr-1', 'org-1');

        self::assertNotNull($creative);
        self::assertNull($creative->scanStatus);
        self::assertNull($creative->assetUrl);
        self::assertNull($creative->width);
    }

    public function testFindIsTenantScoped(): void
    {
        $this->seedCreative();

        self::assertNotNull($this->repo->findByIdInOrganization('cr-1', 'org-1'));
        self::assertNull($this->repo->findByIdInOrganization('cr-1', 'org-OTHER'));
    }

    public function testReviewQueueReturnsOnlySubmittedAndInReview(): void
    {
        $this->seedCreative(['id' => 'cr-draft', 'review_status' => 'draft']);
        $this->seedCreative(['id' => 'cr-sub', 'review_status' => 'submitted']);
        $this->seedCreative(['id' => 'cr-rev', 'review_status' => 'in_review']);
        $this->seedCreative(['id' => 'cr-appr', 'review_status' => 'approved']);
        $this->seedCreative(['id' => 'cr-rej', 'review_status' => 'rejected']);

        $ids = array_map(static fn ($c) => $c->id, $this->repo->listReviewQueue('org-1', 10, 0));

        self::assertSame(['cr-rev', 'cr-sub'], $ids); // ordered by id
    }

    public function testReviewQueueIsPaginated(): void
    {
        $this->seedCreative(['id' => 'cr-a', 'review_status' => 'submitted']);
        $this->seedCreative(['id' => 'cr-b', 'review_status' => 'submitted']);
        $this->seedCreative(['id' => 'cr-c', 'review_status' => 'submitted']);

        $page = $this->repo->listReviewQueue('org-1', 1, 1);
        self::assertCount(1, $page);
        self::assertSame('cr-b', $page[0]->id);
    }

    public function testIdsByCampaignAndIdsWithCampaign(): void
    {
        $this->seedCreative(['id' => 'cr-x', 'campaign_id' => 'cmp-1']);
        $this->seedCreative(['id' => 'cr-y', 'campaign_id' => 'cmp-1']);
        $this->seedCreative(['id' => 'cr-z', 'campaign_id' => 'cmp-2']);
        $this->seedCreative(['id' => 'cr-none', 'campaign_id' => null]);

        self::assertEqualsCanonicalizing(['cr-x', 'cr-y'], $this->repo->idsByCampaign('org-1', 'cmp-1'));
        self::assertEqualsCanonicalizing(['cr-x', 'cr-y', 'cr-z'], $this->repo->idsWithCampaign('org-1'));
    }
}
