<?php

declare(strict_types=1);

use NeneServe\Http\Kernel;
use NeneServe\Http\Request;
use NeneServe\Marketplace\Invoice\HttpInvoiceClient;
use NeneServe\Measurement\FileEventStore;
use NeneServe\Upstream\Deal\HttpDealClient;
use NeneServe\Upstream\Records\HttpRecordsClient;
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
// The real Invoice client is used only when configured (handoff contract);
// otherwise the kernel default in-memory Fake is used. HTTP only, net-only.
$invoiceBase = getenv('NENE_INVOICE_API_BASE_URL');
$invoiceToken = getenv('NENE_INVOICE_SERVICE_TOKEN');
$invoiceClient = (is_string($invoiceBase) && $invoiceBase !== '' && is_string($invoiceToken) && $invoiceToken !== '')
    ? new HttpInvoiceClient($invoiceBase, $invoiceToken)
    : null;

// NeNe Records read client is used only when configured (read-only, ADR 0002).
$recordsBase = getenv('NENE_RECORDS_API_BASE_URL');
$recordsToken = getenv('NENE_RECORDS_SERVICE_TOKEN');
$recordsClient = (is_string($recordsBase) && $recordsBase !== '' && is_string($recordsToken) && $recordsToken !== '')
    ? new HttpRecordsClient($recordsBase, $recordsToken)
    : null;

$dealBase = getenv('NENE_DEAL_API_BASE_URL');
$dealToken = getenv('NENE_DEAL_SERVICE_TOKEN');
$dealClient = (is_string($dealBase) && $dealBase !== '' && is_string($dealToken) && $dealToken !== '')
    ? new HttpDealClient($dealBase, $dealToken)
    : null;

$kernel = new Kernel(
    tokens: new FileTokenStore(dirname(__DIR__) . '/var/tokens.json'),
    events: new FileEventStore(dirname(__DIR__) . '/var/events.json'),
    frequencyCaps: new FileFrequencyCapStore(dirname(__DIR__) . '/var/frequency.json'),
    invoiceClient: $invoiceClient,
    records: $recordsClient,
    dealClient: $dealClient,
);

$kernel->handle(Request::fromGlobals())->send();
