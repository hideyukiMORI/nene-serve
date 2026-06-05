<?php

declare(strict_types=1);

namespace NeneServe\Tests\Serving\Creatives;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneServe\Serving\Creative;
use NeneServe\Serving\CreativeRepositoryInterface;
use NeneServe\Serving\Creatives\GetCreativeHandler;
use NeneServe\Serving\Creatives\ListCreativesHandler;
use NeneServe\Serving\Creatives\ReviewQueueHandler;
use NeneServe\Serving\CreativeType;
use NeneServe\Serving\ReviewStatus;
use NeneServe\Tenant\Auth\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class CreativesReadHandlerTest extends TestCase
{
    private const CLAIMS = ['org' => 'org-acme', 'role' => 'org_admin', 'sub' => 'u-1'];

    public function testListReturnsAllCreatives(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new ListCreativesHandler($this->repo(), $this->json($psr17), $this->problem($psr17));

        $response = $handler->handle($this->request($psr17, 'GET', '/admin/creatives'));

        self::assertSame(200, $response->getStatusCode());
        /** @var array{creatives: list<mixed>} $body */
        $body = json_decode((string) $response->getBody(), true);
        self::assertCount(3, $body['creatives']);
    }

    public function testGetReturns404WhenMissing(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new GetCreativeHandler($this->repo(found: false), $this->json($psr17), $this->problem($psr17));

        $request = $this->request($psr17, 'GET', '/admin/creatives/x')
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['id' => 'x']);

        self::assertSame(404, $handler->handle($request)->getStatusCode());
    }

    public function testReviewQueueOnlyListsReviewable(): void
    {
        $psr17 = new Psr17Factory();
        $handler = new ReviewQueueHandler($this->repo(), $this->json($psr17), $this->problem($psr17));

        $response = $handler->handle($this->request($psr17, 'GET', '/admin/review-queue'));

        self::assertSame(200, $response->getStatusCode());
        /** @var array{creatives: list<mixed>} $body */
        $body = json_decode((string) $response->getBody(), true);
        // 1 submitted + 1 in_review; the approved one is excluded.
        self::assertCount(2, $body['creatives']);
    }

    private function request(Psr17Factory $psr17, string $method, string $path): ServerRequestInterface
    {
        return $psr17->createServerRequest($method, $path)->withAttribute(AdminAuthMiddleware::CLAIMS_ATTRIBUTE, self::CLAIMS);
    }

    private function json(Psr17Factory $psr17): JsonResponseFactory
    {
        return new JsonResponseFactory($psr17, $psr17);
    }

    private function problem(Psr17Factory $psr17): ProblemDetailsResponseFactory
    {
        return new ProblemDetailsResponseFactory($psr17, $psr17);
    }

    private function repo(bool $found = true): CreativeRepositoryInterface
    {
        $list = [
            new Creative('cr-1', 'org-acme', CreativeType::Image, ReviewStatus::Approved, 'https://acme.test/a'),
            new Creative('cr-2', 'org-acme', CreativeType::Image, ReviewStatus::Submitted, 'https://acme.test/b'),
            new Creative('cr-3', 'org-acme', CreativeType::Html5Bundle, ReviewStatus::InReview, 'https://acme.test/c'),
        ];

        return new class ($list, $found) implements CreativeRepositoryInterface {
            /** @param list<Creative> $list */
            public function __construct(private readonly array $list, private readonly bool $found)
            {
            }

            public function findByIdInOrganization(string $id, string $organizationId): ?Creative
            {
                return $this->found ? $this->list[0] : null;
            }

            public function listByOrganization(string $organizationId): array
            {
                return $this->list;
            }

            public function save(Creative $creative): void
            {
            }

            public function idsByCampaign(string $organizationId, string $campaignId): array
            {
                return [];
            }

            public function idsWithCampaign(string $organizationId): array
            {
                return [];
            }
        };
    }
}
