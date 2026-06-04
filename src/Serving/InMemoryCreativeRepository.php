<?php

declare(strict_types=1);

namespace NeneServe\Serving;

final class InMemoryCreativeRepository implements CreativeRepositoryInterface
{
    /** @var list<Creative> */
    private array $creatives;

    /** @param list<Creative> $creatives */
    public function __construct(array $creatives = [])
    {
        $this->creatives = $creatives;
    }

    public function findByIdInOrganization(string $id, string $organizationId): ?Creative
    {
        foreach ($this->creatives as $c) {
            if ($c->id === $id && $c->organizationId === $organizationId) {
                return $c;
            }
        }

        return null;
    }
}
