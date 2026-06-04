<?php

declare(strict_types=1);

use NeneServe\Http\Request;
use NeneServe\Support\KernelFactory;

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

// Persistence is selected from the environment (api-security §6): when DB_HOST is
// set the kernel is wired to the MySQL-backed PDO repositories; otherwise it boots
// the file/in-memory development defaults for `php -S`. Sibling HTTP clients are
// used only when their base URL + token are configured. See Support\KernelFactory.
$kernel = KernelFactory::create(dirname(__DIR__) . '/var');

$kernel->handle(Request::fromGlobals())->send();
