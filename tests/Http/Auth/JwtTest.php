<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http\Auth;

use NeneServe\Http\Auth\Jwt;
use NeneServe\Http\Auth\JwtException;
use PHPUnit\Framework\TestCase;

final class JwtTest extends TestCase
{
    public function testRoundTripVerifiesClaims(): void
    {
        $jwt = new Jwt('top-secret');
        $token = $jwt->issue(['sub' => 'user-1', 'org' => 'org-1', 'role' => 'org_admin']);

        $claims = $jwt->verify($token);

        self::assertSame('user-1', $claims['sub']);
        self::assertSame('org-1', $claims['org']);
        self::assertSame('org_admin', $claims['role']);
    }

    public function testTamperedSignatureIsRejected(): void
    {
        $token = (new Jwt('top-secret'))->issue(['sub' => 'user-1']);

        $this->expectException(JwtException::class);
        (new Jwt('top-secret'))->verify($token . 'x');
    }

    public function testWrongSecretIsRejected(): void
    {
        $token = (new Jwt('top-secret'))->issue(['sub' => 'user-1']);

        $this->expectException(JwtException::class);
        (new Jwt('different-secret'))->verify($token);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $token = (new Jwt('top-secret'))->issue(['sub' => 'user-1'], -10);

        $this->expectException(JwtException::class);
        (new Jwt('top-secret'))->verify($token);
    }

    public function testMalformedTokenIsRejected(): void
    {
        $this->expectException(JwtException::class);
        (new Jwt('top-secret'))->verify('not-a-jwt');
    }
}
