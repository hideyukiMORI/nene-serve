<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Review;

use NeneServe\Serving\Review\VideoAcceptance;
use NeneServe\Serving\UseCase\CreativeValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Video creative acceptance (ADR 0021 §3): MP4/WebM over HTTPS, an image poster,
 * and a bounded duration — each constraint probed from both sides.
 */
final class VideoAcceptanceTest extends TestCase
{
    private const ASSET = 'https://cdn.example.com/ad.mp4';
    private const POSTER = 'https://cdn.example.com/poster.jpg';
    private const DEST = 'https://advertiser.example.com/landing';

    public function testAcceptsAValidVideo(): void
    {
        VideoAcceptance::assertValid(self::ASSET, self::POSTER, self::DEST, 30);
        $this->expectNotToPerformAssertions();
    }

    /** @return iterable<string, array{string}> */
    public static function acceptedVideoFormats(): iterable
    {
        yield 'mp4' => ['https://cdn.example.com/a.mp4'];
        yield 'webm' => ['https://cdn.example.com/a.webm'];
        yield 'uppercase' => ['https://cdn.example.com/A.MP4'];
    }

    #[DataProvider('acceptedVideoFormats')]
    public function testAcceptsAllowlistedVideoFormats(string $assetUrl): void
    {
        VideoAcceptance::assertValid($assetUrl, self::POSTER, self::DEST, 30);
        $this->expectNotToPerformAssertions();
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedVideoFormats(): iterable
    {
        yield 'mov' => ['https://cdn.example.com/a.mov'];
        yield 'avi' => ['https://cdn.example.com/a.avi'];
        yield 'no extension' => ['https://cdn.example.com/a'];
        yield 'image as video' => ['https://cdn.example.com/a.png'];
    }

    #[DataProvider('rejectedVideoFormats')]
    public function testRejectsNonAllowlistedVideoFormats(string $assetUrl): void
    {
        $this->expectException(CreativeValidationException::class);
        VideoAcceptance::assertValid($assetUrl, self::POSTER, self::DEST, 30);
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedPosterFormats(): iterable
    {
        yield 'mp4 as poster' => ['https://cdn.example.com/p.mp4'];
        yield 'gif not allowed for poster' => ['https://cdn.example.com/p.gif'];
        yield 'no extension' => ['https://cdn.example.com/p'];
    }

    #[DataProvider('rejectedPosterFormats')]
    public function testRejectsNonImagePoster(string $posterUrl): void
    {
        $this->expectException(CreativeValidationException::class);
        VideoAcceptance::assertValid(self::ASSET, $posterUrl, self::DEST, 30);
    }

    public function testRejectsInsecureAsset(): void
    {
        $this->expectException(CreativeValidationException::class);
        VideoAcceptance::assertValid('http://cdn.example.com/a.mp4', self::POSTER, self::DEST, 30);
    }

    public function testRejectsInsecurePoster(): void
    {
        $this->expectException(CreativeValidationException::class);
        VideoAcceptance::assertValid(self::ASSET, 'http://cdn.example.com/poster.jpg', self::DEST, 30);
    }

    public function testRejectsInsecureDestination(): void
    {
        $this->expectException(CreativeValidationException::class);
        VideoAcceptance::assertValid(self::ASSET, self::POSTER, 'http://advertiser.example.com', 30);
    }

    public function testAcceptsDurationLowerBoundOne(): void
    {
        VideoAcceptance::assertValid(self::ASSET, self::POSTER, self::DEST, 1);
        $this->expectNotToPerformAssertions();
    }

    public function testAcceptsDurationUpperBoundExactly180(): void
    {
        VideoAcceptance::assertValid(self::ASSET, self::POSTER, self::DEST, 180);
        $this->expectNotToPerformAssertions();
    }

    /** @return iterable<string, array{int}> */
    public static function invalidDurations(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-10];
        yield 'one over max' => [181];
        yield 'far over max' => [3600];
    }

    #[DataProvider('invalidDurations')]
    public function testRejectsOutOfRangeDuration(int $durationSeconds): void
    {
        $this->expectException(CreativeValidationException::class);
        VideoAcceptance::assertValid(self::ASSET, self::POSTER, self::DEST, $durationSeconds);
    }
}
