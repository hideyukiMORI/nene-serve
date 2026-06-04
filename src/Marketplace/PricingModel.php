<?php

declare(strict_types=1);

namespace NeneServe\Marketplace;

/** How billable units convert to money (billing §3.3). Net rate; never tax. */
enum PricingModel: string
{
    case Cpm = 'cpm';   // rate per 1000 impressions
    case Cpc = 'cpc';   // rate per click
    case Flat = 'flat'; // fixed rate per period
}
