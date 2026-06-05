<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use NeneServe\Serving\Csp;
use PHPUnit\Framework\TestCase;

/**
 * The HTML5 sandbox CSP (ADR 0021 §4) is a security contract: each directive
 * below is load-bearing, so the test pins them rather than the whole string.
 */
final class CspTest extends TestCase
{
    public function testSandboxAllowsScriptsButNotSameOriginOrTopNavigation(): void
    {
        $csp = Csp::html5Frame();

        self::assertStringContainsString('sandbox allow-scripts', $csp);
        self::assertStringNotContainsString('allow-same-origin', $csp);
        self::assertStringNotContainsString('allow-top-navigation', $csp);
    }

    public function testDeniesArbitraryEgressAndScriptIsSelfOnly(): void
    {
        $csp = Csp::html5Frame();

        self::assertStringContainsString("default-src 'none'", $csp);
        self::assertStringContainsString("script-src 'self'", $csp);
        // No 'unsafe-eval' anywhere — eval must be blocked.
        self::assertStringNotContainsString('unsafe-eval', $csp);
        self::assertStringContainsString("base-uri 'none'", $csp);
        self::assertStringContainsString("form-action 'none'", $csp);
    }

    public function testDirectivesAreSemicolonSeparated(): void
    {
        $csp = Csp::html5Frame();

        foreach (explode('; ', $csp) as $directive) {
            self::assertNotSame('', trim($directive));
        }
        self::assertStringNotContainsString(';;', $csp);
    }
}
