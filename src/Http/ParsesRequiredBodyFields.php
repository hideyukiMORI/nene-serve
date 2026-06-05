<?php

declare(strict_types=1);

namespace NeneServe\Http;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

/**
 * Extracts and validates required scalar fields from a parsed JSON body, raising
 * a {@see ValidationException} (→ 422) with a field-specific error otherwise.
 */
trait ParsesRequiredBodyFields
{
    /** @param array<string, mixed> $body */
    private function str(array $body, string $key): string
    {
        if (!isset($body[$key]) || !is_string($body[$key]) || $body[$key] === '') {
            throw new ValidationException([new ValidationError($key, sprintf('%s is required.', $key), 'required')]);
        }

        return $body[$key];
    }

    /** @param array<string, mixed> $body */
    private function int(array $body, string $key): int
    {
        if (!isset($body[$key]) || !is_int($body[$key])) {
            throw new ValidationException([new ValidationError($key, sprintf('%s must be an integer.', $key), 'invalid')]);
        }

        return $body[$key];
    }
}
