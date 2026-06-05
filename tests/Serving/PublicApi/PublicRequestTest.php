<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\PublicApi;

use NeneServe\Serving\PublicApi\PublicRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Helpers for the untrusted public surface. clientIp reads REMOTE_ADDR safely;
 * jsonBody is deliberately lenient (empty/garbage → empty array) so a beacon
 * without a JSON body is not an error.
 */
final class PublicRequestTest extends TestCase
{
    /** @param array<string, mixed> $server */
    private function request(array $server = [], string $body = ''): ServerRequestInterface
    {
        $psr17 = new Psr17Factory();
        $request = $psr17->createServerRequest('POST', '/public/x', $server);

        return $body === '' ? $request : $request->withBody($psr17->createStream($body));
    }

    public function testClientIpReadsRemoteAddr(): void
    {
        self::assertSame('203.0.113.7', PublicRequest::clientIp($this->request(['REMOTE_ADDR' => '203.0.113.7'])));
    }

    public function testClientIpDefaultsToEmptyWhenMissing(): void
    {
        self::assertSame('', PublicRequest::clientIp($this->request([])));
    }

    public function testJsonBodyParsesAnObject(): void
    {
        self::assertSame(['consent' => 'granted'], PublicRequest::jsonBody($this->request([], '{"consent":"granted"}')));
    }

    public function testJsonBodyIsEmptyArrayForEmptyBody(): void
    {
        self::assertSame([], PublicRequest::jsonBody($this->request()));
    }

    public function testJsonBodyIsEmptyArrayForInvalidJson(): void
    {
        self::assertSame([], PublicRequest::jsonBody($this->request([], 'not json{')));
    }

    public function testJsonBodyIsEmptyArrayForJsonScalar(): void
    {
        // A bare JSON number/string is not an object → treated as no body.
        self::assertSame([], PublicRequest::jsonBody($this->request([], '42')));
    }
}
