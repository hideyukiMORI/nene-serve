<?php

declare(strict_types=1);

namespace NeneServe\Tests\Marketplace;

use NeneServe\Marketplace\Campaign;
use NeneServe\Marketplace\FundingStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Campaign serve-eligibility gates (billing §3.1): a campaign funds delivery
 * only when it is active (status active AND not archived) AND funded. Every
 * combination is exercised.
 */
final class CampaignTest extends TestCase
{
    private function campaign(string $status, FundingStatus $funding, ?string $archivedAt = null): Campaign
    {
        return new Campaign('cmp-1', 'org-1', 'adv-1', 'Spring', 'pr-1', 100_000, $status, $funding, true, $archivedAt);
    }

    /** @return iterable<string, array{string, ?string, bool}> */
    public static function activeStates(): iterable
    {
        yield 'active, not archived' => ['active', null, true];
        yield 'active but archived' => ['active', '2026-06-06T00:00:00+00:00', false];
        yield 'draft' => ['draft', null, false];
        yield 'paused' => ['paused', null, false];
        yield 'archived status' => ['archived', '2026-06-06T00:00:00+00:00', false];
    }

    #[DataProvider('activeStates')]
    public function testIsActive(string $status, ?string $archivedAt, bool $expected): void
    {
        self::assertSame($expected, $this->campaign($status, FundingStatus::Funded, $archivedAt)->isActive());
    }

    /** @return iterable<string, array{string, FundingStatus, ?string, bool}> */
    public static function fundedForServe(): iterable
    {
        yield 'active + funded' => ['active', FundingStatus::Funded, null, true];
        yield 'active + unfunded' => ['active', FundingStatus::Unfunded, null, false];
        yield 'draft + funded' => ['draft', FundingStatus::Funded, null, false];
        yield 'active funded but archived' => ['active', FundingStatus::Funded, '2026-06-06T00:00:00+00:00', false];
        yield 'paused + funded' => ['paused', FundingStatus::Funded, null, false];
    }

    #[DataProvider('fundedForServe')]
    public function testIsFundedForServe(string $status, FundingStatus $funding, ?string $archivedAt, bool $expected): void
    {
        self::assertSame($expected, $this->campaign($status, $funding, $archivedAt)->isFundedForServe());
    }
}
