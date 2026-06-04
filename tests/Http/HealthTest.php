<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use PHPUnit\Framework\TestCase;

final class HealthTest extends TestCase
{
    public function testHealthReturns200AndOkStatus(): void
    {
        $response = (new Kernel())->handle(new Request('GET', '/health'));

        self::assertSame(200, $response->status);
        self::assertSame(
            'application/json; charset=utf-8',
            $response->headers['Content-Type'] ?? null,
        );

        /** @var array<string, mixed> $body */
        $body = json_decode($response->body, true);
        self::assertSame('ok', $body['status']);
        self::assertSame('nene-serve', $body['service']);
        self::assertSame(Kernel::VERSION, $body['version']);
    }

    public function testUnknownRouteReturnsProblemJson404(): void
    {
        $response = (new Kernel())->handle(new Request('GET', '/does-not-exist'));

        self::assertSame(404, $response->status);
        self::assertSame(
            'application/problem+json; charset=utf-8',
            $response->headers['Content-Type'] ?? null,
        );

        /** @var array<string, mixed> $body */
        $body = json_decode($response->body, true);
        self::assertSame(404, $body['status']);
        self::assertStringEndsWith('/problems/route-not-found', $body['type']);
    }
}
