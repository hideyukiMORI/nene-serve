<?php

declare(strict_types=1);

namespace NeneServe\Tests\Integration;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\SecureTokenHelper;
use NeneServe\Serving\Token\PdoTokenStore;
use NeneServe\Tests\Support\FixedClock;
use NeneServe\Tests\Support\MysqlTestDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Single-use redemption on real MySQL (#207).
 *
 * The property under test is that the *database* picks one winner: consumption
 * is `UPDATE … WHERE consumed_at IS NULL`, and the guarantee comes from the
 * engine's row locking, not from anything in PHP. SQLite runs the same
 * statement but not the same concurrency, and the file store it replaces got
 * this from an flock that stops working the moment there are two hosts — so the
 * assurance has to be re-taken on the engine production uses.
 *
 * Affected-row counts are asserted directly, because that is the value the
 * store branches on: a driver that reported "rows matched" rather than "rows
 * changed" would make every replay look like a first redemption.
 */
#[Group('integration')]
final class PublicTokenSingleUseMysqlTest extends TestCase
{
    private DatabaseQueryExecutorInterface $db;

    private string $organizationId;

    protected function setUp(): void
    {
        $db = MysqlTestDatabase::fromEnv();

        if ($db === null) {
            self::markTestSkipped('MySQL integration DB not configured (set MYSQL_TEST_HOST).');
        }

        $this->db = $db;
        $this->organizationId = 'it-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            $this->db->execute('DELETE FROM public_tokens WHERE organization_id = ?', [$this->organizationId]);
        }
    }

    public function testAClickTokenIsRedeemedExactlyOnceOnMysql(): void
    {
        $token = $this->store()->issueClickToken(
            $this->organizationId,
            'plc-1',
            'crv-1',
            'https://example.com/landing',
            900,
        );

        $first = $this->store()->consumeClickToken($token);
        $second = $this->store()->consumeClickToken($token);
        $third = $this->store()->consumeClickToken($token);

        self::assertNotNull($first);
        self::assertSame('https://example.com/landing', $first->destinationUrl);
        self::assertNull($second, 'Single-use (ADR 0019 §2).');
        self::assertNull($third);

        $row = $this->db->fetchOne(
            'SELECT consumed_at FROM public_tokens WHERE token_hash = ?',
            [SecureTokenHelper::hash($token)],
        );
        self::assertNotNull($row);
        self::assertNotNull($row['consumed_at'], 'The winning redemption stamps the row.');
    }

    public function testTheConditionalUpdateReportsRowsChangedNotRowsMatchedOnMysql(): void
    {
        $token = $this->store()->issueImpressionToken($this->organizationId, 'plc-1', 'crv-1');
        $hash = SecureTokenHelper::hash($token);

        $firstFlip = $this->db->execute(
            "UPDATE public_tokens SET recorded_at = ? WHERE token_hash = ? AND kind = 'impression' AND recorded_at IS NULL",
            [1754308800, $hash],
        );
        $secondFlip = $this->db->execute(
            "UPDATE public_tokens SET recorded_at = ? WHERE token_hash = ? AND kind = 'impression' AND recorded_at IS NULL",
            [1754308800, $hash],
        );

        // If this ever reported 1/1, every impression replay would read as a
        // first recording and counts would inflate (ADR 0015).
        self::assertSame(1, $firstFlip);
        self::assertSame(0, $secondFlip);
    }

    public function testAnImpressionReplayReportsAlreadyRecordedOnMysql(): void
    {
        $token = $this->store()->issueImpressionToken($this->organizationId, 'plc-1', 'crv-1');

        $first = $this->store()->recordImpression($token);
        $replay = $this->store()->recordImpression($token);

        self::assertNotNull($first);
        self::assertFalse($first->alreadyRecorded);
        self::assertNotNull($replay);
        self::assertTrue($replay->alreadyRecorded);
    }

    private function store(): PdoTokenStore
    {
        return new PdoTokenStore($this->db, new FixedClock('2026-08-04T12:00:00+00:00'));
    }
}
