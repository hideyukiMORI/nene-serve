<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/**
 * Campaign funding state (domain-model). `funded` is set by an operator action
 * (in full marketplace, after Invoice payment). Budget **exhaustion** is derived
 * from spend at serve time, not a stored state (billing §3.2/§3.5).
 */
enum FundingStatus: string
{
    case Unfunded = 'unfunded';
    case Funded = 'funded';
}
