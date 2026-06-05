<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use NeneServe\Serving\DestinationUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Open-redirect safety (ADR 0019/0021). The bar is: https anywhere, http only on
 * loopback. Everything else — other schemes, bare hosts, malformed input — is
 * rejected. Boundaries are probed from every direction because a single false
 * "safe" is an open redirect.
 */
final class DestinationUrlTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function safeUrls(): iterable
    {
        yield 'https host' => ['https://example.com/landing'];
        yield 'https root' => ['https://example.com'];
        yield 'https with port' => ['https://example.com:8443/x'];
        yield 'https with query' => ['https://example.com/x?a=1&b=2'];
        yield 'https uppercase scheme' => ['HTTPS://example.com'];
        yield 'https mixed-case scheme' => ['HtTpS://Example.Com'];
        yield 'http localhost' => ['http://localhost/x'];
        yield 'http localhost port' => ['http://localhost:3000/x'];
        yield 'http 127.0.0.1' => ['http://127.0.0.1/x'];
        yield 'http ipv6 loopback' => ['http://[::1]/x'];
        yield 'http LOCALHOST uppercase host' => ['http://LOCALHOST/x'];
        yield 'http uppercase scheme localhost' => ['HTTP://localhost'];
    }

    #[DataProvider('safeUrls')]
    public function testSafeUrlsAreAccepted(string $url): void
    {
        self::assertTrue(DestinationUrl::isSafe($url));
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeUrls(): iterable
    {
        yield 'http non-loopback host' => ['http://example.com/x'];
        yield 'http public ip' => ['http://93.184.216.34/x'];
        yield 'http subdomain of localhost' => ['http://evil.localhost.attacker.com/x'];
        yield 'ftp' => ['ftp://example.com/x'];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'data uri' => ['data:text/html,<script>alert(1)</script>'];
        yield 'file' => ['file:///etc/passwd'];
        yield 'mailto' => ['mailto:a@b.com'];
        yield 'scheme-relative' => ['//example.com/x'];
        yield 'path only' => ['/just/a/path'];
        yield 'bare host no scheme' => ['example.com'];
        yield 'empty string' => [''];
        yield 'whitespace' => ['   '];
        yield 'https no host' => ['https:///path'];
        yield 'garbage' => ['not a url at all'];
    }

    #[DataProvider('unsafeUrls')]
    public function testUnsafeUrlsAreRejected(string $url): void
    {
        self::assertFalse(DestinationUrl::isSafe($url));
    }
}
