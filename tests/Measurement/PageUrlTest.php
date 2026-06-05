<?php

declare(strict_types=1);

namespace NeneServe\Tests\Measurement;

use NeneServe\Measurement\PageUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Page-URL truncation (privacy P9/N1): keep scheme://host/path, drop query and
 * fragment so tokens/secrets in the query never reach storage.
 */
final class PageUrlTest extends TestCase
{
    /** @return iterable<string, array{?string, ?string}> */
    public static function cases(): iterable
    {
        yield 'null in null out' => [null, null];
        yield 'empty in null out' => ['', null];
        yield 'no scheme/host' => ['/relative/path', null];
        yield 'bare host' => ['example.com', null];
        yield 'garbage' => ['::::', null];

        yield 'strips query' => ['https://example.com/a?token=secret', 'https://example.com/a'];
        yield 'strips fragment' => ['https://example.com/a#frag', 'https://example.com/a'];
        yield 'strips query and fragment' => ['https://example.com/a/b?x=1#f', 'https://example.com/a/b'];
        yield 'no path keeps host only' => ['https://example.com', 'https://example.com'];
        yield 'no path with query' => ['https://example.com?x=1', 'https://example.com'];
        yield 'root path' => ['https://example.com/', 'https://example.com/'];
        yield 'http preserved as scheme' => ['http://example.com/p?a=b', 'http://example.com/p'];
    }

    #[DataProvider('cases')]
    public function testTruncate(?string $input, ?string $expected): void
    {
        self::assertSame($expected, PageUrl::truncate($input));
    }
}
