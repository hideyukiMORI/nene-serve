<?php

declare(strict_types=1);

namespace NeneServe\Retention;

use Nene2\Http\ClockInterface;
use Nene2\Http\UtcClock;

/**
 * The two distinct retention regimes (billing §7, privacy §6, ADR 0022 §7),
 * never conflated:
 *
 * - **Ordinary measurement** (no advertiser money behind it): privacy-first,
 *   shortest viable, configurable.
 * - **Billing-relevant** events (tied to a funded campaign): the money SSOT's
 *   statutory period (JP 7 years, extendable to 10) — MUST NOT be purged early.
 *
 * Spend snapshots and the audit log are never purged by the ordinary job.
 */
final class RetentionPolicy
{
    public function __construct(
        public readonly int $privacyRetentionDays = 400,
        public readonly int $billingStatutoryYears = 7,
        private readonly ClockInterface $clock = new UtcClock(),
    ) {
    }

    /** Ordinary measurement older than this UTC date may be purged. */
    public function ordinaryCutoff(string $now): string
    {
        return gmdate('Y-m-d', strtotime($now . ' -' . $this->privacyRetentionDays . ' days') ?: $this->clock->now()->getTimestamp());
    }

    /** Billing-relevant events may only be purged once older than this UTC date. */
    public function billingCutoff(string $now): string
    {
        return gmdate('Y-m-d', strtotime($now . ' -' . $this->billingStatutoryYears . ' years') ?: $this->clock->now()->getTimestamp());
    }
}
