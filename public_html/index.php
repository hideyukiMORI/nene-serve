<?php

declare(strict_types=1);

use Nene2\Http\ResponseEmitter;
use NeneServe\Http\RuntimeContainerFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Server\RequestHandlerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

// Built-in server: serve real static files (e.g. uploaded assets) directly.
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . (string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($requested !== __DIR__ . '/' && is_file($requested)) {
        return false;
    }
}

// Persistence and sibling HTTP clients are selected from the environment
// (api-security §6): see Http\RuntimeServiceProvider.
$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

$psr17Factory = $container->get(Psr17Factory::class);
assert($psr17Factory instanceof Psr17Factory);

$serverRequestCreator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
);

$application = $container->get(RequestHandlerInterface::class);
assert($application instanceof RequestHandlerInterface);

$response = $application->handle($serverRequestCreator->fromGlobals());

$emitter = $container->get(ResponseEmitter::class);
assert($emitter instanceof ResponseEmitter);
$emitter->emit($response);
