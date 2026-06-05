<?php

declare(strict_types=1);

namespace NeneServe\Http;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

/**
 * Accumulates field-level validation errors over a parsed JSON body and raises
 * a single {@see ValidationException} (→ 422) reporting them all at once — the
 * "collect every error" counterpart to the fail-fast {@see ParsesRequiredBodyFields}.
 * Each accessor returns a safe default when invalid so the caller can keep
 * reading; call {@see self::throwIfInvalid()} before using the values.
 */
final class BodyFieldCollector
{
    /** @var list<ValidationError> */
    private array $errors = [];

    /** @param array<string, mixed> $body */
    public function __construct(private readonly array $body)
    {
    }

    public function requiredString(string $key, string $message, bool $trim = false): string
    {
        $value = isset($this->body[$key]) && is_string($this->body[$key]) ? $this->body[$key] : '';
        if ($trim) {
            $value = trim($value);
        }

        if ($value === '') {
            $this->errors[] = new ValidationError($key, $message, 'required');
        }

        return $value;
    }

    public function requiredInt(string $key, string $message): int
    {
        $value = $this->body[$key] ?? null;
        if (!is_int($value)) {
            $this->errors[] = new ValidationError($key, $message, 'invalid');

            return 0;
        }

        return $value;
    }

    /** @param list<string> $allowed */
    public function oneOf(string $key, array $allowed, string $message, string $default = ''): string
    {
        $value = isset($this->body[$key]) && is_string($this->body[$key]) ? $this->body[$key] : $default;
        if (!in_array($value, $allowed, true)) {
            $this->errors[] = new ValidationError($key, $message, 'invalid');
        }

        return $value;
    }

    public function throwIfInvalid(): void
    {
        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }
    }
}
