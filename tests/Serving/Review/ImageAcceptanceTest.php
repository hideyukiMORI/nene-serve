<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Review;

use NeneServe\Serving\Review\ImageAcceptance;
use NeneServe\Serving\UseCase\CreativeValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Image creative acceptance (ADR 0021 §3). Dimensions are bounded on both ends
 * and the format allowlist + URL safety are checked from every direction.
 */
final class ImageAcceptanceTest extends TestCase
{
    private const ASSET = 'https://cdn.example.com/banner.png';
    private const DEST = 'https://advertiser.example.com/landing';

    public function testAcceptsAValidImage(): void
    {
        ImageAcceptance::assertValid(self::ASSET, self::DEST, 300, 250);
        $this->expectNotToPerformAssertions();
    }

    /** @return iterable<string, array{string}> */
    public static function acceptedFormats(): iterable
    {
        yield 'png' => ['https://cdn.example.com/a.png'];
        yield 'jpg' => ['https://cdn.example.com/a.jpg'];
        yield 'jpeg' => ['https://cdn.example.com/a.jpeg'];
        yield 'gif' => ['https://cdn.example.com/a.gif'];
        yield 'webp' => ['https://cdn.example.com/a.webp'];
        yield 'uppercase extension' => ['https://cdn.example.com/A.PNG'];
        yield 'with query string' => ['https://cdn.example.com/a.png?v=2'];
    }

    #[DataProvider('acceptedFormats')]
    public function testAcceptsAllowlistedFormats(string $assetUrl): void
    {
        ImageAcceptance::assertValid($assetUrl, self::DEST, 10, 10);
        $this->expectNotToPerformAssertions();
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedFormats(): iterable
    {
        yield 'svg (script vector)' => ['https://cdn.example.com/a.svg'];
        yield 'bmp' => ['https://cdn.example.com/a.bmp'];
        yield 'no extension' => ['https://cdn.example.com/a'];
        yield 'html disguised' => ['https://cdn.example.com/a.html'];
    }

    #[DataProvider('rejectedFormats')]
    public function testRejectsNonAllowlistedFormats(string $assetUrl): void
    {
        $this->expectException(CreativeValidationException::class);
        ImageAcceptance::assertValid($assetUrl, self::DEST, 10, 10);
    }

    public function testRejectsInsecureAssetUrl(): void
    {
        $this->expectException(CreativeValidationException::class);
        ImageAcceptance::assertValid('http://cdn.example.com/a.png', self::DEST, 10, 10);
    }

    public function testRejectsInsecureDestinationUrl(): void
    {
        $this->expectException(CreativeValidationException::class);
        ImageAcceptance::assertValid(self::ASSET, 'http://advertiser.example.com', 10, 10);
    }

    public function testAcceptsDimensionLowerBoundOne(): void
    {
        ImageAcceptance::assertValid(self::ASSET, self::DEST, 1, 1);
        $this->expectNotToPerformAssertions();
    }

    public function testAcceptsDimensionUpperBoundExactly2000(): void
    {
        ImageAcceptance::assertValid(self::ASSET, self::DEST, 2000, 2000);
        $this->expectNotToPerformAssertions();
    }

    /** @return iterable<string, array{int|null, int|null}> */
    public static function invalidDimensions(): iterable
    {
        yield 'null width' => [null, 250];
        yield 'null height' => [300, null];
        yield 'both null' => [null, null];
        yield 'zero width' => [0, 250];
        yield 'zero height' => [300, 0];
        yield 'negative width' => [-1, 250];
        yield 'negative height' => [300, -5];
        yield 'width over max' => [2001, 250];
        yield 'height over max' => [300, 2001];
        yield 'both over max' => [3000, 3000];
    }

    #[DataProvider('invalidDimensions')]
    public function testRejectsOutOfRangeDimensions(?int $width, ?int $height): void
    {
        $this->expectException(CreativeValidationException::class);
        ImageAcceptance::assertValid(self::ASSET, self::DEST, $width, $height);
    }
}
