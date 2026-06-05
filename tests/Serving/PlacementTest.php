<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving;

use NeneServe\Serving\Placement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Placement state and origin gating. allowsOrigin() is serve-path CORS: an empty
 * allowlist or a null origin is permitted (opted out / non-browser), otherwise
 * the origin must match exactly. archive() tombstones (ADR 0022) and immutability
 * is preserved.
 */
final class PlacementTest extends TestCase
{
    /**
     * @param list<string> $allowed
     */
    private function placement(array $allowed = [], string $status = 'active', ?string $archivedAt = null): Placement
    {
        return new Placement('plc-1', 'org-1', 'pk_home', $allowed, $status, null, true, null, $archivedAt);
    }

    public function testIsActiveOnlyWhenActiveAndNotArchived(): void
    {
        self::assertTrue($this->placement()->isActive());
        self::assertFalse($this->placement([], 'archived', '2026-06-06T00:00:00+00:00')->isActive());
        self::assertFalse($this->placement([], 'draft')->isActive());
    }

    /** @return iterable<string, array{list<string>, ?string, bool}> */
    public static function originCases(): iterable
    {
        yield 'empty allowlist permits any origin' => [[], 'https://anywhere.com', true];
        yield 'empty allowlist permits null' => [[], null, true];
        yield 'null origin always allowed' => [['https://a.com'], null, true];
        yield 'exact match allowed' => [['https://a.com', 'https://b.com'], 'https://b.com', true];
        yield 'non-member rejected' => [['https://a.com'], 'https://evil.com', false];
        yield 'scheme-sensitive (http vs https)' => [['https://a.com'], 'http://a.com', false];
        yield 'trailing slash is a different origin' => [['https://a.com'], 'https://a.com/', false];
        yield 'case-sensitive match' => [['https://a.com'], 'https://A.com', false];
    }

    /**
     * @param list<string> $allowed
     */
    #[DataProvider('originCases')]
    public function testAllowsOrigin(array $allowed, ?string $origin, bool $expected): void
    {
        self::assertSame($expected, $this->placement($allowed)->allowsOrigin($origin));
    }

    public function testArchiveTombstonesWithoutMutatingOriginal(): void
    {
        $original = $this->placement(['https://a.com']);
        $archived = $original->archive('2026-06-06T00:00:00+00:00');

        self::assertTrue($original->isActive());
        self::assertFalse($archived->isActive());
        self::assertSame('archived', $archived->status);
        self::assertSame('2026-06-06T00:00:00+00:00', $archived->archivedAt);
        self::assertSame(['https://a.com'], $archived->allowedOrigins);
    }

    public function testWithDefaultCreativeIsImmutable(): void
    {
        $original = $this->placement();
        $updated = $original->withDefaultCreative('cr-9');

        self::assertNull($original->defaultCreativeId);
        self::assertSame('cr-9', $updated->defaultCreativeId);
    }
}
