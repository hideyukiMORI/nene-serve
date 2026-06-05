<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use NeneServe\Support\HttpStatusLine;
use PHPUnit\Framework\TestCase;

/**
 * Status-code extraction from the `$http_response_header` array the PHP stream
 * wrapper populates. The first status line wins (redirect chains), and a missing
 * status line yields 0 so callers treat it as a transport failure.
 */
final class HttpStatusLineTest extends TestCase
{
    public function testParsesA200(): void
    {
        self::assertSame(200, HttpStatusLine::statusCode(['HTTP/1.1 200 OK', 'Content-Type: application/json']));
    }

    public function testParsesHttp2StatusLine(): void
    {
        self::assertSame(204, HttpStatusLine::statusCode(['HTTP/2 204']));
    }

    public function testReturnsFirstStatusInARedirectChain(): void
    {
        $headers = [
            'HTTP/1.1 301 Moved Permanently',
            'Location: https://example.com/',
            'HTTP/1.1 200 OK',
        ];

        self::assertSame(301, HttpStatusLine::statusCode($headers));
    }

    public function testParsesClientAndServerErrors(): void
    {
        self::assertSame(404, HttpStatusLine::statusCode(['HTTP/1.1 404 Not Found']));
        self::assertSame(503, HttpStatusLine::statusCode(['HTTP/1.0 503 Service Unavailable']));
    }

    public function testReturnsZeroWhenNoStatusLinePresent(): void
    {
        self::assertSame(0, HttpStatusLine::statusCode(['Content-Type: text/html']));
        self::assertSame(0, HttpStatusLine::statusCode([]));
    }

    public function testIgnoresNonStatusHeaderThatMentionsHttp(): void
    {
        self::assertSame(0, HttpStatusLine::statusCode(['Link: <https://example.com>; rel="next"']));
    }
}
