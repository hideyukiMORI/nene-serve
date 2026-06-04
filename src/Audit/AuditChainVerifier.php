<?php

declare(strict_types=1);

namespace NeneServe\Audit;

/**
 * Verifies a per-tenant audit chain (ADR 0022 §5): each row's stored hash must
 * recompute from its fields + the prior row's hash, and its `previousHash` must
 * equal the prior row's hash. Any edit, reorder, or deleted row breaks the chain.
 */
final class AuditChainVerifier
{
    /**
     * @param list<AuditEvent> $events ordered oldest → newest for one tenant
     */
    public static function verify(array $events): bool
    {
        $previousHash = '';
        foreach ($events as $event) {
            if ($event->previousHash !== $previousHash) {
                return false;
            }
            if (!hash_equals(AuditHasher::of($event), $event->hash)) {
                return false;
            }
            $previousHash = $event->hash;
        }

        return true;
    }
}
