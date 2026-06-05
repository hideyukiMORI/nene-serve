<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MarketplaceRouteRegistrar
{
    public function __construct(
        private ListAdvertisersHandler $listAdvertisers,
        private ListPricingRulesHandler $listPricingRules,
        private ListCampaignsHandler $listCampaigns,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listAdvertisers = $this->listAdvertisers;
        $listPricingRules = $this->listPricingRules;
        $listCampaigns = $this->listCampaigns;

        $router->get('/admin/advertisers', static fn (ServerRequestInterface $request) => $listAdvertisers->handle($request));
        $router->get('/admin/pricing-rules', static fn (ServerRequestInterface $request) => $listPricingRules->handle($request));
        $router->get('/admin/campaigns', static fn (ServerRequestInterface $request) => $listCampaigns->handle($request));
    }
}
