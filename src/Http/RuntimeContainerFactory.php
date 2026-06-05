<?php

declare(strict_types=1);

namespace NeneServe\Http;

use Nene2\DependencyInjection\ContainerBuilder;
use Psr\Container\ContainerInterface;

/**
 * Builds the application container on the NENE2 framework. The PSR-11 container
 * it returns exposes the runtime {@see \Psr\Http\Server\RequestHandlerInterface}
 * and {@see \Nene2\Http\ResponseEmitter} used by `public_html/index.php`.
 */
final readonly class RuntimeContainerFactory
{
    public function __construct(
        private ?string $projectRoot = null,
    ) {
    }

    public function create(): ContainerInterface
    {
        $projectRoot = $this->projectRoot ?? dirname(__DIR__, 2);

        return (new ContainerBuilder())
            ->value(RuntimeServiceProvider::PROJECT_ROOT, $projectRoot)
            ->addProvider(new RuntimeServiceProvider())
            ->build();
    }
}
