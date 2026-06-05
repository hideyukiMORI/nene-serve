<?php

declare(strict_types=1);

namespace NeneServe\Tests\Support;

use NeneServe\Support\Id;
use PHPUnit\Framework\TestCase;

/**
 * Opaque id generation: a stable `prefix-hex` shape, the right hex length for
 * the requested entropy, and uniqueness across calls.
 */
final class IdTest extends TestCase
{
    public function testGenerateHasPrefixAndDefault16HexChars(): void
    {
        $id = Id::generate('adv');

        self::assertMatchesRegularExpression('/^adv-[0-9a-f]{16}$/', $id);
    }

    public function testGenerateHonoursByteLength(): void
    {
        self::assertMatchesRegularExpression('/^plan-[0-9a-f]{24}$/', Id::generate('plan', 12));
        self::assertMatchesRegularExpression('/^x-[0-9a-f]{2}$/', Id::generate('x', 1));
    }

    public function testRandomDefaultsTo32HexChars(): void
    {
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', Id::random());
    }

    public function testRandomHonoursByteLength(): void
    {
        self::assertSame(16, strlen(Id::random(8)));
        self::assertSame(64, strlen(Id::random(32)));
    }

    public function testGeneratesUniqueValues(): void
    {
        $ids = [];
        for ($i = 0; $i < 1000; $i++) {
            $ids[Id::generate('cmp')] = true;
        }

        self::assertCount(1000, $ids);
    }
}
