<?php

declare(strict_types=1);

namespace NeneServe\Http\Auth;

use NeneServe\Http\Request;
use NeneServe\Service\ServiceContext;
use NeneServe\Service\ServiceTokenRepositoryInterface;

/**
 * Resolves a {@see ServiceContext} from an opaque `Authorization: Bearer
 * <service-token>` on the `/api/*` surface (api-security §5). Fail closed:
 * missing/unknown/inactive token → {@see UnauthorizedException}. Scope checks
 * happen at the route guard (insufficient-scope → 403).
 */
final class ServiceTokenMiddleware
{
    public function __construct(
        private readonly ServiceTokenRepositoryInterface $tokens,
    ) {
    }

    public function authenticate(Request $request): ServiceContext
    {
        $header = $request->header('authorization');
        if ($header === null || !str_starts_with($header, 'Bearer ')) {
            throw new UnauthorizedException('Missing service token.');
        }

        $token = $this->tokens->findByPresentedToken(trim(substr($header, 7)));
        if ($token === null) {
            throw new UnauthorizedException('Invalid service token.');
        }

        return $token->context();
    }
}
