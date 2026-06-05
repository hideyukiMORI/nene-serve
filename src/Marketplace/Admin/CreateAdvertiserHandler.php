<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Admin;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneServe\Tenant\Auth\AuthContextResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** POST /admin/advertisers (operationId `createAdvertiser`). Requires `manage_marketplace`. */
final readonly class CreateAdvertiserHandler
{
    public function __construct(
        private CreateAdvertiserUseCaseInterface $createAdvertiser,
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

        $body = JsonRequestBodyParser::parse($request);

        $name = isset($body['name']) && is_string($body['name']) ? $body['name'] : '';

        if ($name === '') {
            throw new ValidationException([new ValidationError('name', 'Name is required.', 'required')]);
        }

        $invoiceClientId = isset($body['invoice_client_id']) && is_string($body['invoice_client_id']) ? $body['invoice_client_id'] : null;

        $output = $this->createAdvertiser->execute(new CreateAdvertiserInput($context->userId, $name, $invoiceClientId));

        return $this->response->create($output->advertiser->toArray(), 201);
    }
}
