<?php

declare(strict_types=1);

namespace NeneServe\Mcp\UseCase;

use RuntimeException;

/** Invalid MCP change-plan input. Maps to `validation-failed` (422). */
final class McpValidationException extends RuntimeException
{
}
