<?php

declare(strict_types=1);

namespace NeneServe\I18n;

final class LocaleCheckResult
{
    /**
     * @param list<string> $errors
     * @param array<string, int> $keyCounts locale code => flattened key count
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $errors,
        public readonly array $keyCounts,
    ) {
    }
}
