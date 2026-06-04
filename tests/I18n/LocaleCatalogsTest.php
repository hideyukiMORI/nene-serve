<?php

declare(strict_types=1);

namespace NeneServe\Tests\I18n;

use NeneServe\I18n\LocaleCatalogs;
use PHPUnit\Framework\TestCase;

final class LocaleCatalogsTest extends TestCase
{
    public function testFlattenProducesDottedKeysAndSkipsMeta(): void
    {
        $keys = LocaleCatalogs::flatten([
            '_meta' => ['locale' => 'en'],
            'embed' => ['loading' => 'x', 'error' => 'y'],
            'admin' => ['metrics' => ['title' => 'z']],
        ]);

        self::assertSame(['admin.metrics.title', 'embed.error', 'embed.loading'], $keys);
    }

    public function testPluralProblemsDetectsUnpairedKeys(): void
    {
        self::assertSame([], LocaleCatalogs::pluralProblems(['x.impressions_one', 'x.impressions_other']));

        $problems = LocaleCatalogs::pluralProblems(['x.impressions_one']);
        self::assertCount(1, $problems);
        self::assertStringContainsString('_other', $problems[0]);
    }

    public function testRealCatalogsAreInParity(): void
    {
        $result = LocaleCatalogs::check(__DIR__ . '/../../locales');

        self::assertTrue($result->ok, implode("\n", $result->errors));
        self::assertSame(LocaleCatalogs::LOCALES, array_keys($result->keyCounts));
        // every locale carries the same number of keys as canonical en
        self::assertSame([$result->keyCounts['en']], array_values(array_unique($result->keyCounts)));
    }

    public function testCheckDetectsMissingAndExtraKeys(): void
    {
        $dir = sys_get_temp_dir() . '/nene-locales-' . bin2hex(random_bytes(6));
        mkdir($dir);
        try {
            file_put_contents($dir . '/en.json', (string) json_encode(['a' => '1', 'b' => '2']));
            foreach (['ja', 'zh-Hans', 'ko', 'de'] as $code) {
                file_put_contents($dir . "/{$code}.json", (string) json_encode(['a' => '1', 'b' => '2']));
            }
            // es is missing 'b' and has an extra 'c'
            file_put_contents($dir . '/es.json', (string) json_encode(['a' => '1', 'c' => '3']));

            $result = LocaleCatalogs::check($dir);
            self::assertFalse($result->ok);
            $joined = implode("\n", $result->errors);
            self::assertStringContainsString("es: missing key 'b'", $joined);
            self::assertStringContainsString("es: extra key 'c'", $joined);
        } finally {
            foreach ((array) glob($dir . '/*.json') as $file) {
                unlink((string) $file);
            }
            rmdir($dir);
        }
    }
}
