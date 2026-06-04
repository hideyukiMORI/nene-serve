<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use RuntimeException;

/** Campaign not found in the tenant. Maps to `campaign-not-found` (404). */
final class CampaignNotFoundException extends RuntimeException
{
}
