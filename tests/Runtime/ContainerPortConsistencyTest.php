<?php

declare(strict_types=1);

namespace NeneServe\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The API port is written down in five places and they must agree.
 *
 * #200: the Dockerfile still bound 8910 nine weeks after the 80xx realignment
 * moved the API to 8010 (#129/#130). Nothing caught it because
 * `docker-compose.yml` overrides the image's `CMD` with its own `command:`, so
 * the local stack listened on the right port and the wrong one only appeared
 * when the image ran **without** compose — `docker run`, a registry pull, a
 * production deploy. Exactly the kind of thing found *during* a first deploy.
 *
 * Each extractor throws when its pattern no longer matches, rather than
 * returning null and comparing nothing. A consistency test whose parser has
 * quietly stopped finding anything passes forever — the same failure the
 * #199 work was about, one layer up.
 */
final class ContainerPortConsistencyTest extends TestCase
{
    private const EXPECTED_PORT = '8010';

    public function testEveryDeclarationOfTheApiPortAgrees(): void
    {
        $dockerfile = $this->read('Dockerfile');
        $compose = $this->read('docker-compose.yml');

        $found = [
            'Dockerfile EXPOSE' => $this->extract('/^EXPOSE\s+(\d+)$/m', $dockerfile, 'Dockerfile EXPOSE'),
            'Dockerfile CMD' => $this->extract('/"0\.0\.0\.0:(\d+)"/', $dockerfile, 'Dockerfile CMD bind'),
            'compose ports (host)' => $this->extract('/-\s*"(\d+):\d+"/', $compose, 'compose published port'),
            'compose ports (container)' => $this->extract('/-\s*"\d+:(\d+)"/', $compose, 'compose container port'),
            'compose command' => $this->extract('/command:\s*php\s+-S\s+0\.0\.0\.0:(\d+)/', $compose, 'compose command bind'),
            'APP_PORT (.env.example)' => $this->extract('/^APP_PORT=(\d+)$/m', $this->read('.env.example'), 'APP_PORT'),
            'vite proxy fallback' => $this->extract(
                "/projectEnv\['APP_PORT'\]\s*\?\?\s*'(\d+)'/",
                $this->read('frontend/vite.config.ts'),
                'vite APP_PORT fallback',
            ),
        ];

        foreach ($found as $where => $port) {
            self::assertSame(
                self::EXPECTED_PORT,
                $port,
                sprintf(
                    '%s declares port %s; serve owns the 80xx lane and the API port is %s (CLAUDE.md port registry). '
                    . 'An image that binds a port nothing else names is invisible under compose and breaks on `docker run`.',
                    $where,
                    $port,
                    self::EXPECTED_PORT,
                ),
            );
        }
    }

    private function read(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException("Cannot read {$relativePath} — the port consistency test has nothing to check.");
        }

        return $contents;
    }

    /**
     * Returns the captured port, or throws when the pattern no longer matches —
     * a silently empty check is worse than a failing one.
     */
    private function extract(string $pattern, string $subject, string $what): string
    {
        if (preg_match($pattern, $subject, $matches) !== 1) {
            throw new RuntimeException(
                "Could not find {$what}. The file changed shape; update this test rather than letting it check nothing.",
            );
        }

        return $matches[1];
    }
}
