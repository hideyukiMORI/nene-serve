<?php

declare(strict_types=1);

namespace NeneServe\Upstream\Records;

use RuntimeException;

/** A Records read failed at the transport level (distinct from "not found"). */
final class RecordsClientException extends RuntimeException
{
}
