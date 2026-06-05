<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** POST /admin/campaigns/{id}/billing-periods (operationId `openBillingPeriod`). Requires `manage_marketplace`. */
final readonly class OpenBillingPeriodHandler
{
    public function __construct(
        private OpenBillingPeriodUseCaseInterface $open,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = AuthContextResolver::fromRequest($request);

        if ($context === null) {
            return $this->problemDetails->create($request, 'unauthorized', 'Unauthorized', 401, 'Authentication is required.');
        }

        $campaignId = Router::param($request, 'id') ?? '';

        $body = JsonRequestBodyParser::parse($request);
        $start = isset($body['period_start']) && is_string($body['period_start']) ? $body['period_start'] : '';
        $end = isset($body['period_end']) && is_string($body['period_end']) ? $body['period_end'] : '';

        $errors = [];

        if ($start === '') {
            $errors[] = new ValidationError('period_start', 'period_start (YYYY-MM-DD) is required.', 'required');
        }

        if ($end === '') {
            $errors[] = new ValidationError('period_end', 'period_end (YYYY-MM-DD) is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $period = $this->open->execute(new OpenBillingPeriodInput($context->userId, $campaignId, $start, $end))->period;

        return $this->response->create($period->toArray(), 201);
    }
}
