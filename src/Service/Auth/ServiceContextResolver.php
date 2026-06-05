<?php

declare(strict_types=1);

namespace NeneServe\Service\Auth;

use NeneServe\Service\ServiceContext;
use Psr\Http\Message\ServerRequestInterface;

/** Reads the {@see ServiceContext} placed on the request by {@see ServiceAuthMiddleware}. */
final class ServiceContextResolver
{
    public static function fromRequest(ServerRequestInterface $request): ?ServiceContext
    {
        $context = $request->getAttribute(ServiceAuthMiddleware::CONTEXT_ATTRIBUTE);

        return $context instanceof ServiceContext ? $context : null;
    }
}
