<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use NeneServe\Serving\UseCase\CreativeScanFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class CreativeScanFailedExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof CreativeScanFailedException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'creative-scan-failed', 'Bundle scan is not clean', 422, $exception->getMessage());
    }
}
