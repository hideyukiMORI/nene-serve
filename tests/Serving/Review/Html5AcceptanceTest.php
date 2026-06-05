<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Review;

use NeneServe\Serving\Review\Html5Acceptance;
use NeneServe\Serving\UseCase\CreativeValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * HTML5 bundle acceptance (ADR 0021 §1/§4). Size and asset-count are bounded on
 * both ends; the entry-document content policy is probed for every forbidden
 * construct (remote script, eval, top navigation) in multiple casings.
 */
final class Html5AcceptanceTest extends TestCase
{
    private const MAX_SIZE = 2_097_152; // 2 MiB
    private const MAX_ASSETS = 50;
    private const DEST = 'https://advertiser.example.com/landing';
    private const SAFE_HTML = '<!doctype html><html><body><script>var x=1;</script></body></html>';

    public function testAcceptsAValidBundle(): void
    {
        Html5Acceptance::assertValid(1024, 5, self::DEST, self::SAFE_HTML);
        $this->expectNotToPerformAssertions();
    }

    public function testAcceptsSizeLowerBoundOne(): void
    {
        Html5Acceptance::assertValid(1, 1, self::DEST, self::SAFE_HTML);
        $this->expectNotToPerformAssertions();
    }

    public function testAcceptsSizeUpperBoundExactlyMax(): void
    {
        Html5Acceptance::assertValid(self::MAX_SIZE, self::MAX_ASSETS, self::DEST, self::SAFE_HTML);
        $this->expectNotToPerformAssertions();
    }

    /** @return iterable<string, array{int}> */
    public static function invalidSizes(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'one over max' => [self::MAX_SIZE + 1];
        yield 'far over max' => [10_000_000];
    }

    #[DataProvider('invalidSizes')]
    public function testRejectsOutOfRangeSize(int $sizeBytes): void
    {
        $this->expectException(CreativeValidationException::class);
        Html5Acceptance::assertValid($sizeBytes, 5, self::DEST, self::SAFE_HTML);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidAssetCounts(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-3];
        yield 'one over max' => [self::MAX_ASSETS + 1];
    }

    #[DataProvider('invalidAssetCounts')]
    public function testRejectsOutOfRangeAssetCount(int $assetCount): void
    {
        $this->expectException(CreativeValidationException::class);
        Html5Acceptance::assertValid(1024, $assetCount, self::DEST, self::SAFE_HTML);
    }

    public function testRejectsInsecureDestination(): void
    {
        $this->expectException(CreativeValidationException::class);
        Html5Acceptance::assertValid(1024, 5, 'http://advertiser.example.com', self::SAFE_HTML);
    }

    /** @return iterable<string, array{string}> */
    public static function forbiddenContent(): iterable
    {
        yield 'remote script src' => ['<script src="https://evil.com/x.js"></script>'];
        yield 'remote script src spaced' => ['<script   src = "x.js"></script>'];
        yield 'remote script src uppercase' => ['<SCRIPT SRC="x.js"></SCRIPT>'];
        yield 'eval lowercase' => ['<script>eval("x")</script>'];
        yield 'eval uppercase' => ['<script>EVAL("x")</script>'];
        yield 'window.top' => ['<script>window.top.location="x"</script>'];
        yield 'window.parent' => ['<script>window.parent.postMessage(1)</script>'];
        yield 'top.location' => ['<script>top.location="x"</script>'];
        yield 'target _top double quote' => ['<a target="_top" href="x">go</a>'];
        yield 'target _top single quote' => ["<a target='_top' href='x'>go</a>"];
        yield 'target _TOP uppercase' => ['<a TARGET="_TOP" href="x">go</a>'];
    }

    #[DataProvider('forbiddenContent')]
    public function testRejectsForbiddenEntryDocumentContent(string $html): void
    {
        $this->expectException(CreativeValidationException::class);
        Html5Acceptance::assertValid(1024, 5, self::DEST, $html);
    }

    public function testAcceptsInlineScriptWithoutForbiddenConstructs(): void
    {
        Html5Acceptance::assertValid(1024, 5, self::DEST, '<script>document.getElementById("a").click();</script>');
        $this->expectNotToPerformAssertions();
    }
}
