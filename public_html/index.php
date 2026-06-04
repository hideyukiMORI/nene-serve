<?php

declare(strict_types=1);

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;

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

(new Kernel())->handle(Request::fromGlobals())->send();
