<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Serving\ScanStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Creative serve-gating and the public render projection. Only an approved
 * creative serves; the serve payload must never leak internal ids/org/review
 * metadata and must default video autoplay off (ADR 0021 §3).
 */
final class CreativeTest extends TestCase
{
    private function image(ReviewStatus $status = ReviewStatus::Approved, ?ScanStatus $scan = null): Creative
    {
        return new Creative(
            'cr-1',
            'org-1',
            CreativeType::Image,
            $status,
            'https://adv.example.com/l',
            'https://cdn.example.com/a.png',
            300,
            250,
            1,
            'u-1',
            null,
            null,
            null,
            null,
            null,
            $scan,
            null,
        );
    }

    /** @return iterable<string, array{ReviewStatus, bool}> */
    public static function servableStates(): iterable
    {
        yield 'approved is servable' => [ReviewStatus::Approved, true];
        yield 'draft not servable' => [ReviewStatus::Draft, false];
        yield 'submitted not servable' => [ReviewStatus::Submitted, false];
        yield 'in_review not servable' => [ReviewStatus::InReview, false];
        yield 'rejected not servable' => [ReviewStatus::Rejected, false];
        yield 'changes_requested not servable' => [ReviewStatus::ChangesRequested, false];
    }

    #[DataProvider('servableStates')]
    public function testIsServable(ReviewStatus $status, bool $expected): void
    {
        self::assertSame($expected, $this->image($status)->isServable());
    }

    /** @return iterable<string, array{ReviewStatus, bool}> */
    public static function editableStates(): iterable
    {
        yield 'draft editable' => [ReviewStatus::Draft, true];
        yield 'changes_requested editable' => [ReviewStatus::ChangesRequested, true];
        yield 'submitted locked' => [ReviewStatus::Submitted, false];
        yield 'in_review locked' => [ReviewStatus::InReview, false];
        yield 'approved locked' => [ReviewStatus::Approved, false];
        yield 'rejected locked' => [ReviewStatus::Rejected, false];
    }

    #[DataProvider('editableStates')]
    public function testIsEditable(ReviewStatus $status, bool $expected): void
    {
        self::assertSame($expected, $this->image($status)->isEditable());
    }

    public function testIsScanCleanOnlyForCleanStatus(): void
    {
        self::assertTrue($this->image(ReviewStatus::Approved, ScanStatus::Clean)->isScanClean());
        self::assertFalse($this->image(ReviewStatus::Approved, ScanStatus::Pending)->isScanClean());
        self::assertFalse($this->image(ReviewStatus::Approved, ScanStatus::Flagged)->isScanClean());
        self::assertFalse($this->image(ReviewStatus::Approved, null)->isScanClean());
    }

    public function testServePayloadForImageExposesOnlyRenderFields(): void
    {
        $payload = $this->image()->toServePayload();

        self::assertSame(
            ['type' => 'image', 'asset_url' => 'https://cdn.example.com/a.png', 'width' => 300, 'height' => 250],
            $payload,
        );
        self::assertArrayNotHasKey('id', $payload);
        self::assertArrayNotHasKey('organization_id', $payload);
        self::assertArrayNotHasKey('review_status', $payload);
        self::assertArrayNotHasKey('destination_url', $payload);
    }

    public function testServePayloadForVideoAddsPosterAndDisablesAutoplay(): void
    {
        $video = new Creative(
            'cr-2',
            'org-1',
            CreativeType::Video,
            ReviewStatus::Approved,
            'https://adv.example.com/l',
            'https://cdn.example.com/a.mp4',
            640,
            360,
            1,
            'u-1',
            null,
            'https://cdn.example.com/p.jpg',
            30,
            null,
            null,
            null,
            null,
        );

        $payload = $video->toServePayload();

        self::assertSame('https://cdn.example.com/p.jpg', $payload['poster_url']);
        self::assertFalse($payload['autoplay']);
    }

    public function testServePayloadFiltersNullFields(): void
    {
        $html5 = new Creative(
            'cr-3',
            'org-1',
            CreativeType::Html5Bundle,
            ReviewStatus::Approved,
            'https://adv.example.com/l',
            null,
            null,
            null,
            1,
            'u-1',
            null,
            null,
            null,
            'bundle-1',
            4096,
            ScanStatus::Clean,
            null,
        );

        $payload = $html5->toServePayload();

        self::assertSame(['type' => 'html5_bundle'], $payload);
        self::assertArrayNotHasKey('asset_url', $payload);
        self::assertArrayNotHasKey('width', $payload);
    }

    public function testWithReviewProducesAnewImmutableInstance(): void
    {
        $draft = $this->image(ReviewStatus::Draft);
        $submitted = $draft->withReview(ReviewStatus::Submitted, 'u-2', null);

        self::assertSame(ReviewStatus::Draft, $draft->reviewStatus);
        self::assertSame(ReviewStatus::Submitted, $submitted->reviewStatus);
        self::assertSame('u-2', $submitted->submittedBy);
        self::assertSame('cr-1', $submitted->id);
    }

    public function testWithReviewKeepsExistingSubmitterWhenNotProvided(): void
    {
        $submitted = $this->image(ReviewStatus::Submitted)->withReview(ReviewStatus::InReview, null, null);

        self::assertSame('u-1', $submitted->submittedBy);
    }

    public function testWithReviewCarriesRejectionReason(): void
    {
        $rejected = $this->image(ReviewStatus::InReview)->withReview(ReviewStatus::Rejected, 'reviewer', 'Off-brand.');

        self::assertSame('Off-brand.', $rejected->reviewReason);
    }
}
