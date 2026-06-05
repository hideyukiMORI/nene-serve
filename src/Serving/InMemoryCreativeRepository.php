<?php

declare(strict_types=1);

namespace NeneServe\Serving;

final class InMemoryCreativeRepository implements CreativeRepositoryInterface
{
    /** @var array<string, Creative> keyed by creative id */
    private array $creatives = [];

    /** @param list<Creative> $creatives */
    public function __construct(array $creatives = [])
    {
        foreach ($creatives as $creative) {
            $this->creatives[$creative->id] = $creative;
        }
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Creative
    {
        $creative = $this->creatives[$id] ?? null;

        return ($creative !== null && $creative->organizationId === $organizationId) ? $creative : null;
    }

    public function listByOrganization(string $organizationId, int $limit, int $offset): array
    {
        $matches = array_values(array_filter(
            $this->creatives,
            static fn (Creative $c): bool => $c->organizationId === $organizationId,
        ));

        return array_slice($matches, $offset, $limit);
    }

    public function listReviewQueue(string $organizationId, int $limit, int $offset): array
    {
        $matches = array_values(array_filter(
            $this->creatives,
            static fn (Creative $c): bool => $c->organizationId === $organizationId
                && in_array($c->reviewStatus, [ReviewStatus::Submitted, ReviewStatus::InReview], true),
        ));

        return array_slice($matches, $offset, $limit);
    }

    public function save(Creative $creative): void
    {
        $this->creatives[$creative->id] = $creative;
    }

    public function idsByCampaign(string $organizationId, string $campaignId): array
    {
        $ids = [];
        foreach ($this->creatives as $c) {
            if ($c->organizationId === $organizationId && $c->campaignId === $campaignId) {
                $ids[] = $c->id;
            }
        }

        return $ids;
    }

    public function idsWithCampaign(string $organizationId): array
    {
        $ids = [];
        foreach ($this->creatives as $c) {
            if ($c->organizationId === $organizationId && $c->campaignId !== null) {
                $ids[] = $c->id;
            }
        }

        return $ids;
    }
}
