<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Token;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\SecureTokenHelper;
use NeneServe\Serving\Token\PdoTokenStore;
use NeneServe\Tests\Support\FixedClock;
use NeneServe\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * The token store has to hold two properties the file store could only hold on
 * one host: a click token is **single-use**, and an impression token is
 * **idempotent** (ADR 0019 §2, ADR 0015). Both are asserted here across
 * separate store instances, because in production the issuing request and the
 * redeeming request are different processes — possibly on different machines.
 */
final class PdoTokenStoreTest extends TestCase
{
    public function testAClickTokenIssuedByOneInstanceRedeemsOnAnother(): void
    {
        $db = $this->db();

        $token = $this->store($db)->issueClickToken('org-1', 'plc-1', 'crv-1', 'https://example.com/landing', 900);

        // A different process entirely — this is the case var/tokens.json cannot
        // serve once a second host exists.
        $redirect = $this->store($db)->consumeClickToken($token);

        self::assertNotNull($redirect);
        self::assertSame('org-1', $redirect->organizationId);
        self::assertSame('https://example.com/landing', $redirect->destinationUrl);
    }

    public function testAClickTokenCannotBeRedeemedTwice(): void
    {
        $db = $this->db();
        $token = $this->store($db)->issueClickToken('org-1', 'plc-1', 'crv-1', 'https://example.com/landing', 900);

        self::assertNotNull($this->store($db)->consumeClickToken($token));
        self::assertNull($this->store($db)->consumeClickToken($token), 'Single-use (ADR 0019 §2).');
    }

    public function testAnExpiredClickTokenFailsClosed(): void
    {
        $db = $this->db();
        $token = $this->store($db, '2026-08-04T12:00:00+00:00')
            ->issueClickToken('org-1', 'plc-1', 'crv-1', 'https://example.com/landing', 900);

        // One second past the TTL.
        self::assertNull($this->store($db, '2026-08-04T12:15:01+00:00')->consumeClickToken($token));
    }

    public function testAnUnknownClickTokenFailsClosed(): void
    {
        self::assertNull($this->store($this->db())->consumeClickToken('never-issued'));
    }

    public function testAnExpiredTokenIsNotConsumedByTheAttemptThatFails(): void
    {
        $db = $this->db();
        $token = $this->store($db, '2026-08-04T12:00:00+00:00')
            ->issueClickToken('org-1', 'plc-1', 'crv-1', 'https://example.com/landing', 900);

        $this->store($db, '2026-08-04T12:15:01+00:00')->consumeClickToken($token);

        // The failed attempt must not have stamped consumed_at — the conditions
        // are part of the write, so a miss changes nothing.
        $row = $db->fetchOne('SELECT consumed_at FROM public_tokens WHERE token_hash = ?', [SecureTokenHelper::hash($token)]);
        self::assertNotNull($row);
        self::assertNull($row['consumed_at']);
    }

    public function testAnImpressionReplayReportsAlreadyRecordedInsteadOfCountingAgain(): void
    {
        $db = $this->db();
        $token = $this->store($db)->issueImpressionToken('org-1', 'plc-1', 'crv-1');

        $first = $this->store($db)->recordImpression($token);
        $replay = $this->store($db)->recordImpression($token);

        self::assertNotNull($first);
        self::assertFalse($first->alreadyRecorded);
        self::assertNotNull($replay);
        self::assertTrue($replay->alreadyRecorded, 'A replay must never inflate a count (ADR 0015).');
    }

    public function testAnUnknownImpressionTokenIsNull(): void
    {
        self::assertNull($this->store($this->db())->recordImpression('never-issued'));
    }

    public function testAFrameTokenResolvesRepeatedlyWithinItsTtlAndNotAfter(): void
    {
        $db = $this->db();
        $token = $this->store($db, '2026-08-04T12:00:00+00:00')->issueFrameToken('org-1', 'crv-1', 600);

        // Reusable, unlike a click token.
        self::assertNotNull($this->store($db, '2026-08-04T12:05:00+00:00')->resolveFrameToken($token));
        self::assertNotNull($this->store($db, '2026-08-04T12:09:59+00:00')->resolveFrameToken($token));
        self::assertNull($this->store($db, '2026-08-04T12:10:01+00:00')->resolveFrameToken($token));
    }

    public function testTheRawTokenIsNeverStored(): void
    {
        $db = $this->db();
        $token = $this->store($db)->issueClickToken('org-1', 'plc-1', 'crv-1', 'https://example.com/landing', 900);

        $rows = $db->fetchAll('SELECT token_hash FROM public_tokens');

        self::assertCount(1, $rows);
        self::assertSame(SecureTokenHelper::hash($token), (string) $rows[0]['token_hash']);
        self::assertNotSame($token, (string) $rows[0]['token_hash']);
    }

    public function testKindsDoNotCrossRedeem(): void
    {
        $db = $this->db();
        $frame = $this->store($db)->issueFrameToken('org-1', 'crv-1', 600);
        $impression = $this->store($db)->issueImpressionToken('org-1', 'plc-1', 'crv-1');

        // A frame token is not a click token even though both live in one table.
        self::assertNull($this->store($db)->consumeClickToken($frame));
        self::assertNull($this->store($db)->recordImpression($frame));
        self::assertNull($this->store($db)->resolveFrameToken($impression));
    }

    private function db(): DatabaseQueryExecutorInterface
    {
        return TestDatabase::withSchema('public_tokens');
    }

    /** A fresh store over the same database — a separate request's object graph. */
    private function store(DatabaseQueryExecutorInterface $db, string $instant = '2026-08-04T12:00:00+00:00'): PdoTokenStore
    {
        return new PdoTokenStore($db, new FixedClock($instant));
    }
}
