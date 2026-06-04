<?php

declare(strict_types=1);

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Measurement\FileEventStore;
use NeneServe\Serving\Frequency\FileFrequencyCapStore;
use NeneServe\Serving\Token\FileTokenStore;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    // Fallback PSR-4 autoloader so the scaffold boots before `composer install`.
    spl_autoload_register(static function (string $class): void {
        $prefix = 'NeneServe\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $path = dirname(__DIR__) . '/src/'
            . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

// Built-in server: serve real static files (assets) directly.
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . (string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($requested !== __DIR__ . '/' && is_file($requested)) {
        return false;
    }
}

// Tokens must persist across the separate serve/click HTTP requests, so the live
// entry point uses a file-backed store (the kernel default in-memory store is
// per-request and suits tests). Production swaps a shared store; see #14.
$kernel = new Kernel(
    tokens: new FileTokenStore(dirname(__DIR__) . '/var/tokens.json'),
    events: new FileEventStore(dirname(__DIR__) . '/var/events.json'),
    frequencyCaps: new FileFrequencyCapStore(dirname(__DIR__) . '/var/frequency.json'),
);

$kernel->handle(Request::fromGlobals())->send();
