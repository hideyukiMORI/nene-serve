<?php

declare(strict_types=1);

namespace NeneServe\Serving;

interface CreativeRepositoryInterface
{
    /** Serve-path and admin lookup; tenant-scoped (ADR 0006). */
    public function findByIdInOrganization(string $id, string $organizationId): ?Creative;

    /**
     * @return list<Creative>
     */
    public function listByOrganization(string $organizationId, int $limit, int $offset): array;

    /**
     * Creatives awaiting a review decision (submitted / in_review), tenant-scoped.
     *
     * @return list<Creative>
     */
    public function listReviewQueue(string $organizationId, int $limit, int $offset): array;

    /** Insert or replace a creative version (admin surface). */
    public function save(Creative $creative): void;

    /**
     * Creative ids belonging to a campaign (for billable-spend derivation).
     *
     * @return list<string>
     */
    public function idsByCampaign(string $organizationId, string $campaignId): array;

    /**
     * All billing-relevant creative ids (bound to any campaign) — used to keep
     * their events under statutory retention (billing §7).
     *
     * @return list<string>
     */
    public function idsWithCampaign(string $organizationId): array;
}
